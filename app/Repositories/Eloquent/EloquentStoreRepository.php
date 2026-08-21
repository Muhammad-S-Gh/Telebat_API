<?php

namespace App\Repositories\Eloquent;

use App\Models\Store;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentStoreRepository implements StoreRepositoryInterface
{
    public function findForVendor(int $storeId, int $vendorId): ?Store
    {
        return Store::where('id', $storeId)
            ->where('vendor_id', $vendorId)
            ->first();
    }

    public function paginate(array $filters, string $locale, int $perPage): LengthAwarePaginator
    {
        $jsonPath = '$.' . $locale;
        $search = $filters['search'] ?? null;

        return Store::query()
            ->when($search, function ($query) use ($jsonPath, $search) {
                $query->whereRaw(
                    'JSON_UNQUOTE(JSON_EXTRACT(name, ?)) LIKE ?',
                    [$jsonPath, "%{$search}%"]
                );
            })
            ->paginate($perPage);
    }

    public function latest(int $limit): Collection
    {
        return Store::latest()->take($limit)->get();
    }

    public function create(array $attributes): Store
    {
        return Store::create($attributes);
    }

    public function save(Store $store): Store
    {
        $store->save();

        return $store->fresh();
    }

    public function delete(Store $store): bool
    {
        return (bool) $store->delete();
    }

    public function forVendor(int $vendorId): Collection
    {
        return Store::where('vendor_id', $vendorId)->get();
    }
}
