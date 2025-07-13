<?php

namespace App\Services\Vendor;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\Services\Support\ServiceResult;
use Illuminate\Support\Facades\Gate;

class VendorService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly StoreRepositoryInterface $stores,
    ) {}

    public function storesFor(User $vendor)
    {
        return $this->stores->forVendor($vendor->id);
    }

    public function ordersForStore(Store $store, User $vendor)
    {
        Gate::denyIf($store->vendor_id !== $vendor->id);

        return $this->orders->storeOrdersReadyForFulfillment($store, $vendor->id);
    }

    public function markDelivering(Order $order, User $vendor): ServiceResult
    {
        Gate::denyIf($this->orders->storeFor($order)->vendor_id !== $vendor->id);

        if ($order->status !== 'approved') {
            return ServiceResult::failure(
                "Order status is {$order->status}. Only approved orders can be marked as delivering.",
                400
            );
        }

        return $this->changeStatus($order, 'delivering', 'Order status changed to delivering.');
    }

    public function markDelivered(Order $order, User $vendor): ServiceResult
    {
        Gate::denyIf($this->orders->storeFor($order)->vendor_id !== $vendor->id);

        if ($order->status !== 'delivering') {
            return ServiceResult::failure(
                "Order status is {$order->status}. Only delivering orders can be marked as completed.",
                400
            );
        }

        return $this->changeStatus($order, 'delivered', 'Order status changed to delivered.');
    }

    private function changeStatus(Order $order, string $status, string $message): ServiceResult
    {
        $order->status = $status;
        $order = $this->orders->save($order);
        $order->user->notify(new OrderStatusUpdated($order, $status));

        return ServiceResult::success(['order' => $order], 200, $message);
    }
}
