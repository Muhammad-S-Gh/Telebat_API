<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRequestRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderService
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRequestRepositoryInterface $paymentRequests,
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function currencies()
    {
        return $this->currencies->all();
    }

    public function forUser(User $user)
    {
        return $this->orders->forUser($user);
    }

    public function createFromCart(User $user): ServiceResult
    {
        return DB::transaction(function () use ($user) {
            $locale = app()->getLocale();
            $cart = $this->carts->firstOrCreateForUser($user, ['products']);

            if ($cart->products->isEmpty()) {
                return ServiceResult::failure('Your cart is empty', 422);
            }

            $total = $this->total($cart->products);
            $order = $this->orders->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total' => round($total, 2),
                'store_id' => $cart->products->first()->store_id,
            ]);

            foreach ($cart->products as $product) {
                $quantity = $product->pivot->quantity;

                if ($quantity > $product->quantity) {
                    return ServiceResult::failure("Not enough stock for product: {$product->getName($locale)}", 400);
                }

                $this->orders->attachProduct($order, $product, $quantity, $product->price);
                $this->products->decrementQuantity($product, $quantity);
            }

            $this->carts->clear($cart);
            $paymentRequest = $this->paymentRequests->createForOrder(
                $order,
                array_merge($this->paymentRequestAttributes($order, $user, $total), [
                    'currency' => config('app.currency', 'USD'),
                    'status' => PaymentRequest::status()->pending,
                ])
            );

            return ServiceResult::success([
                'order_id' => $order->fresh()->id,
                'payment_requset' => $paymentRequest,
            ], 201, 'Order created successfully');
        });
    }

    public function updateQuantity(Order $order, User $user, array $data): ServiceResult
    {
        return DB::transaction(function () use ($order, $user, $data) {
            $locale = app()->getLocale();
            $this->orders->loadProductsAndPaymentRequest($order);
            $product = $order->products->firstWhere('id', $data['product_id']);

            if (! $product) {
                return ServiceResult::failure('Product not found in order', 404);
            }

            $newQuantity = $data['quantity'];
            $oldQuantity = $product->pivot->quantity;
            $difference = $newQuantity - $oldQuantity;

            if ($difference > 0) {
                if ($product->quantity < $difference) {
                    return ServiceResult::failure("Not enough stock for product: {$product->getName($locale)}", 400);
                }

                $this->products->decrementQuantity($product, $difference);
            } elseif ($difference < 0) {
                $this->products->incrementQuantity($product, abs($difference));
            }

            $this->orders->updateProductQuantity($order, $data['product_id'], $newQuantity);
            $this->orders->loadProductsAndPaymentRequest($order);
            $total = $this->total($order->products);
            $order->total = round($total, 2);
            $order = $this->orders->save($order);
            $this->orders->loadProductsAndPaymentRequest($order);

            $paymentRequest = $this->paymentRequests->update(
                $order->paymentRequest,
                $this->paymentRequestAttributes($order, $user, $total)
            );

            return ServiceResult::success([
                'order' => $order,
                'order_products' => $order->products,
                'payment_request' => $paymentRequest,
            ], 200, 'Order updated successfully');
        });
    }

    public function removeProduct(Order $order, array $data): ServiceResult
    {
        return DB::transaction(function () use ($order, $data) {
            $this->orders->loadProductsAndPaymentRequest($order);
            $product = $order->products->firstWhere('id', $data['product_id']);

            if (! $product) {
                return ServiceResult::failure('Product not found in order', 404);
            }

            $this->products->incrementQuantity($product, $product->pivot->quantity);
            $this->orders->detachProduct($order, $data['product_id']);
            $this->orders->loadProductsAndPaymentRequest($order);

            if ($order->products->isEmpty()) {
                $this->paymentRequests->deleteForOrder($order);
                $this->orders->delete($order);

                return ServiceResult::success([], 200, 'Order deleted successfully.');
            }

            $total = $this->total($order->products);
            $order->total = round($total, 2);
            $order = $this->orders->save($order);
            $this->orders->loadProductsAndPaymentRequest($order);
            $paymentRequest = $this->paymentRequests->update(
                $order->paymentRequest,
                $this->simplePaymentRequestAttributes($order, $total)
            );

            return ServiceResult::success([
                'order' => $order,
                'order_products' => $order->products,
                'payment_request' => $paymentRequest,
            ], 200, 'product removed successfully');
        });
    }

    public function cancel(Order $order): void
    {
        Gate::authorize('cancelOrder', $order);

        DB::transaction(function () use ($order) {
            $this->orders->loadProducts($order);

            foreach ($order->products as $product) {
                $this->products->incrementQuantity($product, $product->pivot->quantity);
            }

            foreach ($order->products as $product) {
                $this->orders->detachProduct($order, $product->id);
            }

            $this->paymentRequests->deleteForOrder($order);
            $order->status = 'canceled';
            $this->orders->save($order);
        });
    }

    private function total($products): float
    {
        return (float) $products->sum(fn (Product $product) => $product->price * $product->pivot->quantity);
    }

    private function paymentRequestAttributes(Order $order, User $user, float $total): array
    {
        return array_merge($this->simplePaymentRequestAttributes($order, $total), [
            'user_id' => $user->id,
            'description' => [
                'en' => "Payment due for Order #{$order->id} totaling \${$total} for user {$user->id} Mr. {$user->first_name} {$user->last_name} at " . Carbon::now() . '.',
                'ar' => "المبلغ المستحق للطلب رقم #{$order->id} بمجموع \${$total} للمستخدم السيد {$user->first_name} {$user->last_name} (معرف المستخدم: {$user->id}) بتاريخ " . Carbon::now() . '.',
            ],
        ]);
    }

    private function simplePaymentRequestAttributes(Order $order, float $total): array
    {
        return [
            'title' => [
                'en' => "Invoice for Order #{$order->id}",
                'ar' => "فاتورة للطلب رقم #{$order->id}",
            ],
            'description' => [
                'en' => "Payment due for Order #{$order->id} totaling \${$total}",
                'ar' => "المبلغ المستحق للطلب رقم #{$order->id} بمجموع \${$total}",
            ],
            'price' => round($total, 2),
        ];
    }
}
