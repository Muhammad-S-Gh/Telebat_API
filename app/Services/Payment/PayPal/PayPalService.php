<?php

namespace App\Services\Payment\PayPal;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\ShipOrderNotification;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Repositories\Contracts\PaymentRequestRepositoryInterface;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;


class PayPalService extends PaymentService
{
    private PayPalHttpClient $client;

    public function __construct(
        private readonly PaymentRequestRepositoryInterface $paymentRequests,
        private readonly CurrencyRepositoryInterface $currencies,
    ) {
        $environment = config('services.paypal.mode') === 'live'
            ? new ProductionEnvironment(config('services.paypal.client_id'), config('services.paypal.client_secret'))
            : new SandboxEnvironment(config('services.paypal.client_id'), config('services.paypal.client_secret'));

        $this->client = new PayPalHttpClient($environment);
    }

    public function checkout(User $user, int $paymentRequestId, int $currencyId): array
    {
        $paymentRequest = $this->paymentRequests->findPendingForUser($paymentRequestId, $user);
        $currency = $this->currencies->findActive($currencyId);

        $description = $paymentRequest->description;
        if (is_array($description)) {
            $description = $description[app()->getLocale()] ?? reset($description) ?: '';
        }
        $paypalOrderData = $this->createOrder($paymentRequest->price, $currency->code, (string) $description);

        $user->payments()->create([
            'model_type' => PaymentRequest::class,
            'model_id' => $paymentRequest->id,
            'payment_request_id' => $paymentRequest->id,
            'payment_method' => Payment::methods()->paypal,
            'status' => Payment::status()->pending,
            'paypal_order_id' => $paypalOrderData['paypal_order_id'],
            'price' => $paymentRequest->price,
            'currency' => strtolower($currency->code),
        ]);

        return $paypalOrderData;
    }

    public function cancel(string $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $payment = Payment::where('paypal_order_id', $orderId)
                ->where('status', Payment::status()->pending)
                ->lockForUpdate()
                ->first();
            if (! $payment) {
                return;
            }

            $paymentRequest = $payment->paymentRequest;
            $payment->update(['status' => Payment::status()->canceled]);
            $paymentRequest?->update(['status' => PaymentRequest::status()->canceled]);

            if ($paymentRequest?->payable_type === Order::class) {
                Order::whereKey($paymentRequest->payable_id)->where('status', 'pending')->update(['status' => 'canceled']);
            }
        });
    }

    public function createOrder(float $total, string $currency, string $description)
    {
        if ($total <= 0) {
            throw new \Exception(__("messages.the_total_price_must_be_greater_than_zero"), 400);
        }

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');

        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $total
                    ],
                    'description' => $description
                ],
            ],
            'application_context' => [
                'return_url' => route('paypal.sucess'),
                'cancel_url' => route('paypal.cancel'),
            ],
        ];

        $response = $this->client->execute($request);
        $approvalUrl = null;

        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                $approvalUrl = $link->href;
                break;
            }
        }

        if (!$approvalUrl) {
            throw new \Exception('Unable to generate PayPal approvl URL');
        }

        return [
            'paypal_order_id' => $response->result->id,
            'approval_url' => $approvalUrl
        ];
    }

    public function captureOrder(string $orderId)
    {
        try {

            $orderDetails = $this->getOrderDetails($orderId);

            $amountPaid = $orderDetails->purchase_units[0]->amount->value;

            $payment = Payment::where('paypal_order_id', $orderId)->firstOrFail();

            $paymentRequest = $payment->paymentRequest;
            $total = $paymentRequest->price;
            $totalInCents = (int)round((((float)$total) * 100));

            return DB::transaction(function () use ($payment, $paymentRequest, $orderDetails, $amountPaid, $totalInCents, $orderId) {

                $amountPaidInCents = (int)round(($amountPaid) * 100);

                if ((string)$totalInCents !== (string)$amountPaidInCents) {
                    throw new \RuntimeException(__("messages.total_not_equal_paid"), 400);
                }

                $request = new OrdersCaptureRequest($orderId);
                $request->prefer('return=representation');
                $response = $this->client->execute($request);

                if ($response->result->status === 'COMPLETED') {
                    $paymentRequest->update(['status' => PaymentRequest::status()->completed]);
                    $captureDetails = $response->result->purchase_units[0]->payments->captures[0] ?? null;

                    $payment->update([
                        'paypal_capture_response' => json_encode($captureDetails),
                        'currency' => $orderDetails->purchase_units[0]->amount->currency_code,
                        'status' => $response->result->status,
                        'description' => $response->result->purchase_units[0]->description ?? null,
                        'response' => json_encode($response),
                        'completed_at' => now()->toDateString()
                    ]);

                    if ($paymentRequest->payable_type === Order::class) {
                        $order = Order::findOrFail($paymentRequest->payable_id);
                        $order->update(['status' => 'approved']);
                        $user = $order->user;
                        $vendor = $order->store()->first()->vendor;
                        if ($user) {
                            $user->notify(new OrderStatusUpdated($order, $order->status));
                        }
                        if ($vendor) {
                            $vendor->notify(new ShipOrderNotification($order));
                        }
                    }
                    return success([
                        'data' => $paymentRequest
                    ]);
                }

                throw new \RuntimeException(__('messages.payment_not_approved'), 400);
            });
        } catch (\Exception $e) {
            return error($e->getMessage(), $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }




    public function getOrderDetails(string $orderId)
    {
        try {
            $request = new OrdersGetRequest($orderId);
            $response = $this->client->execute($request);
            return $response->result;
        } catch (\Exception $e) {
            throw new \Exception('Unable to fetch PayPal order details' . $e->getMessage());
        }
    }
}
