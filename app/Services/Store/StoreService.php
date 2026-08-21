<?php

namespace App\Services\Store;

use App\Models\Store;
use App\Models\User;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class StoreService
{
    public function __construct(private readonly StoreRepositoryInterface $stores) {}

    public function paginate(array $filters)
    {
        return $this->stores->paginate(
            $filters,
            app()->getLocale(),
            config('pagination.per_page', 5)
        );
    }

    public function create(array $data, User $user, ?UploadedFile $image): array
    {
        $store = $this->stores->create([
            'name' => ['ar' => $data['ar_name'], 'en' => $data['en_name']],
            'section_id' => $data['section_id'],
            'vendor_id' => $user->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'image' => $image?->store('stores', 'public'),
        ]);

        return [
            'name' => $store->getName(app()->getLocale()),
            'section_id' => $store->section_id,
            'vendor_id' => $store->vendor_id,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'image' => $store->image,
        ];
    }

    public function update(Store $store, array $data, User $user, ?UploadedFile $image): array
    {
        Gate::denyIf($store->vendor_id !== $user->id, 'Unauthorized action');
        if ($image) {
            $this->deleteImage($store->image);
            $store->image = $image->store('stores', 'public');
        }

        $this->mergeName($store, $data['ar_name'] ?? null, $data['en_name'] ?? null);

        foreach (['section_id', 'latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $data)) {
                $store->{$field} = $data[$field];
            }
        }

        if (! $store->isDirty() && ! $image) {
            return ['store' => $store, 'message' => 'Nothing updated'];
        }

        return ['store' => $this->stores->save($store), 'message' => null];
    }

    public function delete(Store $store, User $user): void
    {
        Gate::denyIf($store->vendor_id !== $user->id, 'Unauthorized action');
        $this->deleteImage($store->image);
        $this->stores->delete($store);
    }

    public function forVendor(User $user)
    {
        return $this->stores->forVendor($user->id);
    }

    private function mergeName(Store $store, ?string $ar, ?string $en): void
    {
        $override = array_filter(['ar' => $ar, 'en' => $en], fn ($value) => $value !== null);

        if ($override === []) {
            return;
        }

        $store->name = array_merge(['ar' => null, 'en' => null], $store->name ?? [], $override);
    }

    private function deleteImage(?string $image): void
    {
        if ($image && Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }
    }
}
