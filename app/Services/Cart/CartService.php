<?php

namespace App\Services\Cart;

use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Support\ServiceResult;

class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function paginatedProducts(User $user)
    {
        $cart = $this->carts->firstOrCreateForUser($user);

        return $this->carts->paginateProducts($cart, config('pagination.per_page'));
    }

    public function addProduct(User $user, array $data): ServiceResult
    {
        $cart = $this->carts->firstOrCreateForUser($user, ['products']);
        $product = $this->products->findOrFail($data['product_id']);

        $firstItem = $cart->products->first();
        $storeId = $firstItem ? $firstItem->store_id : $product->store_id;

        if ($storeId !== $product->store_id) {
            return ServiceResult::failure([
                'existing_cart_store_id' => $storeId,
                'attempted_product_store_id' => $product->store_id,
            ], 400, 'All items in a cart must belong to the same store.');
        }

        $existingProduct = $this->carts->findProduct($cart, $product->id);
        $quantity = $data['quantity'];

        if ($existingProduct) {
            $quantity += $existingProduct->pivot->quantity;
            $this->carts->updateProductQuantity($cart, $product->id, $quantity);
        } else {
            $this->carts->attachProduct($cart, $product, $quantity);
        }

        return ServiceResult::success(['product' => $product], 200, 'Product added to your cart');
    }

    public function updateProduct(User $user, array $data): ServiceResult
    {
        $cart = $this->carts->firstOrCreateForUser($user);
        $product = $this->carts->findProduct($cart, $data['product_id']);

        if (! $product) {
            return ServiceResult::failure('Product not found in cart !!', 404);
        }

        $this->carts->updateProductQuantity($cart, $product->id, $data['quantity']);

        return ServiceResult::success([], 200, 'Yor cart product updated successfully');
    }

    public function removeProduct(User $user, array $data): ServiceResult
    {
        $cart = $this->carts->firstOrCreateForUser($user);
        $product = $this->carts->findProduct($cart, $data['product_id']);

        if (! $product) {
            return ServiceResult::failure('Product not found in your cart !!', 404);
        }

        $this->carts->detachProduct($cart, $product->id);

        return ServiceResult::success(['product' => $product], 200, 'Product removed successfully');
    }

    public function clear(User $user): void
    {
        $cart = $this->carts->firstOrCreateForUser($user);
        $this->carts->clear($cart);
    }
}
