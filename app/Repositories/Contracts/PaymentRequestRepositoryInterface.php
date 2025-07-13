<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\PaymentRequest;

interface PaymentRequestRepositoryInterface
{
    public function createForOrder(Order $order, array $attributes): PaymentRequest;

    public function update(PaymentRequest $paymentRequest, array $attributes): PaymentRequest;

    public function deleteForOrder(Order $order): void;
}
