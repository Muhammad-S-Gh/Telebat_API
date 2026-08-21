<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function findOrFail(int $id): Product;

    public function latestForHome(int $userId, int $limit): Collection;

    public function paginateForCatalog(array $filters, int $userId, string $locale, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Product;

    public function save(Product $product): Product;

    public function delete(Product $product): bool;

    public function incrementQuantity(Product $product, int $quantity): void;

    public function decrementQuantity(Product $product, int $quantity): void;

    public function isFavorite(Product $product, int $userId): bool;

    public function favoritesForUser(User $user): Collection;

    public function addFavorite(User $user, Product $product): void;

    public function removeFavorite(User $user, Product $product): void;
}
