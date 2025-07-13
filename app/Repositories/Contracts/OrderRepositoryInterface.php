<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function forUser(User $user): Collection;

    public function loadProducts(Order $order): Order;

    public function loadProductsAndPaymentRequest(Order $order): Order;

    public function storeFor(Order $order): Store;

    public function create(array $attributes): Order;

    public function attachProduct(Order $order, Product $product, int $quantity, float $price): void;

    public function updateProductQuantity(Order $order, int $productId, int $quantity): void;

    public function detachProduct(Order $order, int $productId): void;

    public function save(Order $order): Order;

    public function refresh(Order $order): Order;

    public function delete(Order $order): bool;

    public function storeOrdersReadyForFulfillment(Store $store, int $favoriteUserId): Collection;
}
