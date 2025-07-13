<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function paginateForCatalog(array $filters, int $userId, string $locale, int $perPage): LengthAwarePaginator
    {
        return Product::query()
            ->withCount([
                'favoriteBy as is_favorite' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->search($filters['search'] ?? null, $locale)
            ->filterBySection($filters['section_id'] ?? null)
            ->filterByStore($filters['store_id'] ?? null)
            ->filterByPrice($filters['price_range'] ?? null)
            ->filterByquantity($filters['quantity_range'] ?? null)
            ->sort($filters['sort_field'] ?? null, $filters['sort_dir'] ?? 'asc', $locale)
            ->paginate($perPage);
    }

    public function create(array $attributes): Product
    {
        return Product::create($attributes);
    }

    public function save(Product $product): Product
    {
        $product->save();

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function incrementQuantity(Product $product, int $quantity): void
    {
        $product->increment('quantity', $quantity);
    }

    public function decrementQuantity(Product $product, int $quantity): void
    {
        $product->decrement('quantity', $quantity);
    }

    public function isFavorite(Product $product, int $userId): bool
    {
        return $product->favoriteBy()->wherePivot('user_id', $userId)->exists();
    }

    public function favoritesForUser(User $user): Collection
    {
        return $user->favoriteProducts()->get();
    }

    public function addFavorite(User $user, Product $product): void
    {
        $user->favoriteProducts()->syncWithoutDetaching($product->id);
    }

    public function removeFavorite(User $user, Product $product): void
    {
        $user->favoriteProducts()->detach($product->id);
    }
}
