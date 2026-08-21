<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Repositories\Contracts\PaymentRequestRepositoryInterface;

class EloquentPaymentRequestRepository implements PaymentRequestRepositoryInterface
{
    public function createForOrder(Order $order, array $attributes): PaymentRequest
    {
        return PaymentRequest::create(array_merge($attributes, [
            'payable_id' => $order->id,
            'payable_type' => Order::class,
        ]));
    }

    public function update(PaymentRequest $paymentRequest, array $attributes): PaymentRequest
    {
        $paymentRequest->update($attributes);

        return $paymentRequest->fresh();
    }

    public function deleteForOrder(Order $order): void
    {
        $order->paymentRequest()->delete();
    }

    public function findPendingForUser(int $id, User $user): PaymentRequest
    {
        return PaymentRequest::whereKey($id)
            ->where('user_id', $user->id)
            ->where('status', PaymentRequest::status()->pending)
            ->firstOrFail();
    }
}
