<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function forUser(User $user): Collection
    {
        return $user->orders()->with(['products'])->get();
    }

    public function loadProducts(Order $order): Order
    {
        return $order->load('products');
    }

    public function loadProductsAndPaymentRequest(Order $order): Order
    {
        return $order->load(['products', 'paymentRequest']);
    }

    public function storeFor(Order $order): Store
    {
        return $order->store()->firstOrFail();
    }

    public function create(array $attributes): Order
    {
        return Order::create($attributes);
    }

    public function attachProduct(Order $order, Product $product, int $quantity, float $price): void
    {
        $order->products()->attach($product->id, [
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    public function updateProductQuantity(Order $order, int $productId, int $quantity): void
    {
        $order->products()->updateExistingPivot($productId, ['quantity' => $quantity]);
    }

    public function detachProduct(Order $order, int $productId): void
    {
        $order->products()->detach($productId);
    }

    public function save(Order $order): Order
    {
        $order->save();

        return $order->fresh();
    }

    public function refresh(Order $order): Order
    {
        return $order->refresh();
    }

    public function delete(Order $order): bool
    {
        return (bool) $order->delete();
    }

    public function storeOrdersReadyForFulfillment(Store $store, int $favoriteUserId): Collection
    {
        return $store->orders()
            ->whereIn('status', ['approved', 'delivering'])
            ->with([
                'products' => function ($query) use ($favoriteUserId) {
                    $query->withCount([
                        'favoriteBy as is_favorite' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $favoriteUserId),
                    ]);
                },
            ])
            ->get();
    }
}
