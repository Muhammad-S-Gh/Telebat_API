<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\RemoveFromOrderRequest;
use App\Http\Requests\Order\UpdateOrderQuantityRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function getCurrencies()
    {
        return success(['currencies' => $this->orders->currencies()]);
    }

    public function showOrder(Request $request, Order $order)
    {
        return success(['order' => $this->orders->showForUser($order, $request->user())]);
    }

    public function viewOrders(Request $request)
    {
        return success(['orders' => OrderResource::collection($this->orders->forUser($request->user()))], 200, 'Your orders.');
    }

    public function createOrder(Request $request)
    {
        return $this->respond($this->orders->createFromCart($request->user()));
    }

    public function updateOrderQuantity(UpdateOrderQuantityRequest $request, Order $order)
    {
        return $this->respond($this->orders->updateQuantity($order, $request->user(), $request->validated()));
    }

    public function removeProduct(RemoveFromOrderRequest $request, Order $order)
    {
        return $this->respond($this->orders->removeProduct($order, $request->validated()));
    }

    public function cancelOrder(Request $request, Order $order)
    {
        $this->orders->cancel($order);

        return response()->json(['message' => 'Order canceled successfully.'], 200);
    }

    private function respond(\App\Services\Support\ServiceResult $result)
    {
        return $result->successful
            ? success($result->data, $result->status, $result->message)
            : error($result->message ?? $result->errors, $result->status, $result->errors ?? []);
    }
}
