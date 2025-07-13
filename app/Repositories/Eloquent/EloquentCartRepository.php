<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function firstOrCreateForUser(User $user, array $relations = []): Cart
    {
        $cart = $user->cart()->firstOrCreate();

        if ($relations !== []) {
            $cart->load($relations);
        }

        return $cart;
    }

    public function paginateProducts(Cart $cart, int $perPage): LengthAwarePaginator
    {
        return $cart->products()->paginate($perPage);
    }

    public function findProduct(Cart $cart, int $productId): ?Product
    {
        return $cart->products()->where('product_id', $productId)->first();
    }

    public function attachProduct(Cart $cart, Product $product, int $quantity): void
    {
        $cart->products()->attach($product->id, ['quantity' => $quantity]);
    }

    public function updateProductQuantity(Cart $cart, int $productId, int $quantity): void
    {
        $cart->products()->updateExistingPivot($productId, ['quantity' => $quantity]);
    }

    public function detachProduct(Cart $cart, int $productId): void
    {
        $cart->products()->detach($productId);
    }

    public function clear(Cart $cart): void
    {
        $cart->products()->detach();
    }
}
