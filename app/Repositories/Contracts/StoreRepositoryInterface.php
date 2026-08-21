<?php

namespace App\Repositories\Contracts;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StoreRepositoryInterface
{
    public function findForVendor(int $storeId, int $vendorId): ?Store;

    public function paginate(array $filters, string $locale, int $perPage): LengthAwarePaginator;

    public function latest(int $limit): Collection;

    public function create(array $attributes): Store;

    public function save(Store $store): Store;

    public function delete(Store $store): bool;

    public function forVendor(int $vendorId): Collection;
}
