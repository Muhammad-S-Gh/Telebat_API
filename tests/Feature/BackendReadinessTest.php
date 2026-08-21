<?php

use App\Models\Section;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRequestRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\Services\Cart\CartService;
use App\Services\Home\HomeService;
use App\Services\Notification\NotificationService;
use App\Services\Order\OrderService;
use App\Services\Payment\PayPal\PayPalService;
use App\Services\Product\ProductService;
use App\Services\Section\SectionService;
use App\Services\Store\StoreService;
use App\Services\Vendor\VendorService;
use App\Repositories\Eloquent\EloquentCartRepository;
use App\Repositories\Eloquent\EloquentCurrencyRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentOrderRepository;
use App\Repositories\Eloquent\EloquentPaymentRequestRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentSectionRepository;
use App\Repositories\Eloquent\EloquentStoreRepository;

it('returns the usual invalid credentials response for an unknown phone number', function () {
    $this->postJson('/api/auth/login', [
        'phone_number' => '+15550000000',
        'password' => 'password',
        'fcm_token' => 'test-device-token',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone_number');
});

it('returns a store under the store key', function () {
    $user = User::factory()->create(['verified_at' => now()]);
    $section = Section::create([
        'name' => ['en' => 'Food'],
        'description' => ['en' => 'Food section'],
    ]);
    $store = Store::create([
        'vendor_id' => $user->id,
        'section_id' => $section->id,
        'name' => ['en' => 'Test Store'],
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/stores/{$store->id}")
        ->assertOk()
        ->assertJsonPath('data.store.id', $store->id)
        ->assertJsonMissingPath('data.section');
});

it('redirects out-of-range section and store pages while preserving empty results', function () {
    $user = User::factory()->create(['verified_at' => now()]);
    $this->actingAs($user, 'sanctum');

    $this->getJson('/api/sections?page=5')
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 0);
    $this->getJson('/api/stores?page=5')
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 0);

    $section = Section::create(['name' => ['en' => 'Food']]);
    Store::create([
        'vendor_id' => $user->id,
        'section_id' => $section->id,
        'name' => ['en' => 'Test Store'],
    ]);

    $this->getJson('/api/sections?page=5')
        ->assertRedirect(route('sections.index', ['page' => 1]));
    $this->getJson('/api/stores?page=5')
        ->assertRedirect(route('stores.index', ['page' => 1]));
});

it('resolves repository bindings and core application services', function () {
    $bindings = [
        CartRepositoryInterface::class => EloquentCartRepository::class,
        CurrencyRepositoryInterface::class => EloquentCurrencyRepository::class,
        NotificationRepositoryInterface::class => EloquentNotificationRepository::class,
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
        PaymentRequestRepositoryInterface::class => EloquentPaymentRequestRepository::class,
        ProductRepositoryInterface::class => EloquentProductRepository::class,
        SectionRepositoryInterface::class => EloquentSectionRepository::class,
        StoreRepositoryInterface::class => EloquentStoreRepository::class,
    ];

    foreach ($bindings as $contract => $implementation) {
        expect($this->app->make($contract))->toBeInstanceOf($implementation);
    }

    config([
        'services.paypal.mode' => 'sandbox',
        'services.paypal.client_id' => 'test-client-id',
        'services.paypal.client_secret' => 'test-client-secret',
    ]);

    foreach ([
        CartService::class,
        HomeService::class,
        NotificationService::class,
        OrderService::class,
        PayPalService::class,
        ProductService::class,
        SectionService::class,
        StoreService::class,
        VendorService::class,
    ] as $service) {
        expect($this->app->make($service))->toBeInstanceOf($service);
    }
});
