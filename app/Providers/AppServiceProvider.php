<?php

namespace App\Providers;

use App\Listeners\RegisterWelcomeMail;
use App\Models\Notification;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRequestRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\Repositories\Eloquent\EloquentCartRepository;
use App\Repositories\Eloquent\EloquentCurrencyRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentOrderRepository;
use App\Repositories\Eloquent\EloquentPaymentRequestRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentSectionRepository;
use App\Repositories\Eloquent\EloquentStoreRepository;
use App\Policies\NotificationPolicy;
use Illuminate\Notifications\DatabaseNotification;
use App\Policies\OrderPolicy;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientInterface::class, Client::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(CurrencyRepositoryInterface::class, EloquentCurrencyRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(PaymentRequestRepositoryInterface::class, EloquentPaymentRequestRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, EloquentSectionRepository::class);
        $this->app->bind(StoreRepositoryInterface::class, EloquentStoreRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            Registered::class,
            RegisterWelcomeMail::class
        );

        Gate::policy(Order::class, OrderPolicy::class);
    }
}
