<?php

namespace App\Http\Controllers;

use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Vendor\VendorStoresResource;
use App\Models\Order;
use App\Models\Store;
use App\Services\Support\ServiceResult;
use App\Services\Vendor\VendorService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(private readonly VendorService $vendors) {}

    public function myStores(Request $request)
    {
        return VendorStoresResource::collection($this->vendors->storesFor($request->user()));
    }

    public function getMyStoreOrders(Request $request, Store $store)
    {
        return success(['orders' => OrderResource::collection($this->vendors->ordersForStore($store, $request->user()))]);
    }

    public function deliverOrder(Request $request, Order $order)
    {
        return $this->respond($this->vendors->markDelivering($order, $request->user()));
    }

    public function completedOrder(Request $request, Order $order)
    {
        return $this->respond($this->vendors->markDelivered($order, $request->user()));
    }

    private function respond(ServiceResult $result)
    {
        return $result->successful
            ? success($result->data, $result->status, $result->message)
            : error($result->message ?? $result->errors, $result->status, $result->errors ?? []);
    }
}
