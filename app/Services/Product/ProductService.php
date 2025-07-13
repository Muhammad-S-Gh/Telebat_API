<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StoreRepositoryInterface $stores,
    ) {}

    public function paginate(array $filters, User $user)
    {
        return $this->products->paginateForCatalog(
            $filters,
            $user->id,
            app()->getLocale(),
            config('pagination.per_page')
        );
    }

    public function create(array $data, User $user, ?UploadedFile $image): array
    {
        $store = $this->stores->findForVendor($data['store_id'], $user->id);
        Gate::denyIf($store === null);

        $imagePath = $image?->store('products', 'public');
        $product = $this->products->create([
            'section_id' => $store->section_id,
            'store_id' => $store->id,
            'name' => ['ar' => $data['ar_name'], 'en' => $data['en_name']],
            'description' => ['ar' => $data['ar_description'], 'en' => $data['en_description']],
            'price' => $data['price'],
            'quantity' => $data['quantity'],
            'image' => $imagePath,
        ]);

        return $this->localizedPayload($product, app()->getLocale());
    }

    public function update(Product $product, array $data, User $user, ?UploadedFile $image): array
    {
        Gate::denyIf($product->store->vendor_id !== $user->id, 'unauthorized action');

        $isFavorite = $this->products->isFavorite($product, $user->id);

        if ($image) {
            $this->deleteImage($product->image);
            $product->image = $image->store('products', 'public');
        }

        $this->mergeTranslation($product, 'name', $data['ar_name'] ?? null, $data['en_name'] ?? null);
        $this->mergeTranslation($product, 'description', $data['ar_description'] ?? null, $data['en_description'] ?? null);

        foreach (['price', 'quantity'] as $field) {
            if (array_key_exists($field, $data)) {
                $product->{$field} = $data[$field];
            }
        }

        if (! $product->isDirty() && ! $image) {
            return [
                'product' => $product,
                'is_favorite' => $isFavorite,
                'message' => 'Nothing to update',
            ];
        }

        return [
            'product' => $this->products->save($product),
            'is_favorite' => $isFavorite,
            'message' => 'Product updated successfully.',
        ];
    }

    public function delete(Product $product, User $user): void
    {
        Gate::denyIf($product->store->vendor_id !== $user->id, 'unauthorized action');
        $this->deleteImage($product->image);
        $this->products->delete($product);
    }

    public function show(Product $product, User $user): array
    {
        return [
            'Product' => $product,
            'is_favorite' => $this->products->isFavorite($product, $user->id),
        ];
    }

    public function favorites(User $user): array
    {
        $locale = app()->getLocale();

        return $this->products
            ->favoritesForUser($user)
            ->map(fn (Product $product) => $this->localizedPayload($product, $locale))
            ->all();
    }

    public function addFavorite(User $user, Product $product): Product
    {
        $this->products->addFavorite($user, $product);

        return $product;
    }

    public function removeFavorite(User $user, Product $product): void
    {
        $this->products->removeFavorite($user, $product);
    }

    private function localizedPayload(Product $product, string $locale): array
    {
        return [
            'id' => $product->id,
            'section_id' => $product->section_id,
            'store_id' => $product->store_id,
            'name' => $product->getName($locale),
            'description' => $product->getDescription($locale),
            'price' => $product->price,
            'quantity' => $product->quantity,
            'image' => $product->image,
        ];
    }

    private function mergeTranslation(Product $product, string $field, ?string $ar, ?string $en): void
    {
        $override = array_filter(['ar' => $ar, 'en' => $en], fn ($value) => $value !== null);

        if ($override === []) {
            return;
        }

        $product->{$field} = array_merge(['ar' => null, 'en' => null], $product->{$field} ?? [], $override);
    }

    private function deleteImage(?string $image): void
    {
        if ($image && Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }
    }
}
