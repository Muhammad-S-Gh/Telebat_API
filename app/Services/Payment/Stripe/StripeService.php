<?php

namespace App\Services\Payment\Stripe;

use App\Http\Requests\Payment\CreatePaymentIntentRequest;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\ShipOrderNotification;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;


class StripeService extends PaymentService
{
    protected $currencyCode;
    protected $endpointSecret;

    public function __construct(protected StripeClient $stripe)
    {
        $this->endpointSecret = config('services.stripe.webhook_secret');
    }


    public function getWebhookSecret()
    {
        return $this->endpointSecret;
    }

    public function createPaymentIntent(CreatePaymentIntentRequest $request)
    {
        $validated = $request->validated();
        $this->currencyCode = Currency::findOrFail($validated['currency_id'])->code;
        $paymentRequest = PaymentRequest::findOrFail($validated['payment_request_id']);
        $user = $request->user();

        try {
            $paymentIntent = $this->stripe->paymentIntents->create(
                [
                    'amount' => (int) round($paymentRequest->price * 100),
                    'currency' => strtolower($this->currencyCode),
                    'payment_method_types' => config('services.stripe.payment_methods'),
                    'metadata' => [
                        'payment_request_id' => $paymentRequest->id,
                        'user_id' => $user->id,
                        'payment_type' => $paymentRequest->payable_type,
                    ],
                ],
                [
                    'idempotency_key' => "payment_intent_{$paymentRequest->id}_{$user->id}",
                ]
            );
            $this->savePayment($user, $paymentIntent, $paymentRequest, $this->currencyCode, Payment::status()->pending);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Unable to create payment intent. Please try again.');
        } catch (\Throwable $e) {
            throw $e;
        }
    }


    public function handleWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->endpointSecret,
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event['type'] === 'payment_intent.succeeded') {
            $pi           = $event['data']['object'];
            $amount       = $pi['amount'];
            $clientSecret = $pi['client_secret'];
            $currency     = $pi['currency'];
            $intentId = $pi->id;

            $payment = Payment::where('stripe_client_secret', $clientSecret)
                ->firstOrFail();

            $expectedCents = (int)round($payment->paymentRequest->price * 100);
            if (
                $amount != $expectedCents
                || strtolower($currency) != strtolower($payment->currency)
            ) {
                return response()->json(['error' => "Discrepancy for intent $clientSecret"], 400);
            }


            DB::transaction(function () use ($payment): void {
                $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($payment->status === Payment::status()->completed) {
                    return;
                }

                $pr = $payment->paymentRequest;
                $payment->update(['status' => Payment::status()->completed]);
                $pr->update(['status' => PaymentRequest::status()->completed]);

                if ($pr->payable_type === Order::class) {
                    $order = Order::findOrFail($pr->payable_id);
                    $order->update(['status' => 'approved']);

                    if ($order->user) {
                        $order->user->notify(new OrderStatusUpdated($order, $order->status));
                    }
                    $vendor = $order->store()->first()?->vendor;
                    if ($vendor) {
                        $vendor->notify(new ShipOrderNotification($order));
                    }
                }
            });

            return ['status' => 'success'];
        }

        return ['status' => 'event not handled'];
    }
}
