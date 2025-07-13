<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CartRepositoryInterface
{
    public function firstOrCreateForUser(User $user, array $relations = []): Cart;

    public function paginateProducts(Cart $cart, int $perPage): LengthAwarePaginator;

    public function findProduct(Cart $cart, int $productId): ?Product;

    public function attachProduct(Cart $cart, Product $product, int $quantity): void;

    public function updateProductQuantity(Cart $cart, int $productId, int $quantity): void;

    public function detachProduct(Cart $cart, int $productId): void;

    public function clear(Cart $cart): void;
}
