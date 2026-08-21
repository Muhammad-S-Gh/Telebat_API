<?php

namespace App\Services\Home;

use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;

class HomeService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly SectionRepositoryInterface $sections,
        private readonly StoreRepositoryInterface $stores,
    ) {}

    public function dashboard(User $user): array
    {
        $locale = app()->getLocale();

        return [
            'latest_products' => $this->products->latestForHome($user->id, 10)->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->getName($locale),
                'description' => $product->getDescription($locale),
                'price' => $product->price,
                'quantity' => $product->quantity,
                'image' => $product->image,
                'store_id' => $product->store_id,
                'section_id' => $product->section_id,
                'is_favorite' => (bool) $product->is_favorite,
            ]),
            'sections' => $this->sections->latest(10)->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->getName($locale),
                'description' => $section->getDescription($locale),
                'image' => $section->image,
            ]),
            'latest_stores' => $this->stores->latest(10)->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->getName($locale),
                'image' => $store->image,
            ]),
        ];
    }
}
