<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;

interface PaymentRequestRepositoryInterface
{
    public function createForOrder(Order $order, array $attributes): PaymentRequest;

    public function update(PaymentRequest $paymentRequest, array $attributes): PaymentRequest;

    public function deleteForOrder(Order $order): void;

    public function findPendingForUser(int $id, User $user): PaymentRequest;
}
