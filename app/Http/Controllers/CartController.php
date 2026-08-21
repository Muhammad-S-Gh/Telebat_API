<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\CartRequest;
use App\Http\Requests\Cart\removeCartRequest;
use App\Http\Resources\Cart\CartProductResource;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(Request $request)
    {
        $products = $this->cart->paginatedProducts($request->user());

        if ($products->isEmpty()) {
            return success([], 200, 'Your cart is empty');
        }

        return success(['cart products' => CartProductResource::collection($products)], 200, 'The products in your cart.');
    }

    public function addToCart(CartRequest $request)
    {
        return $this->respond($this->cart->addProduct($request->user(), $request->validated()));
    }

    public function updateCartItem(CartRequest $request)
    {
        return $this->respond($this->cart->updateProduct($request->user(), $request->validated()));
    }

    public function removeFromCart(removeCartRequest $request)
    {
        return $this->respond($this->cart->removeProduct($request->user(), $request->validated()));
    }

    public function clearCart(Request $request)
    {
        $this->cart->clear($request->user());

        return success([], 200, 'Cart cleared successfully !!');
    }

    private function respond(\App\Services\Support\ServiceResult $result)
    {
        return $result->successful
            ? success($result->data, $result->status, $result->message)
            : error($result->message ?? $result->errors, $result->status, $result->errors ?? []);
    }
}
