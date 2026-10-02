<?php

namespace App\Providers;

use App\Services\Payments\NullPaymentGateway;
use App\Services\Payments\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap for a real provider (e.g. a StripePaymentGateway) when charging is introduced.
        $this->app->bind(PaymentGateway::class, NullPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // Inertia props receive resources directly, without a "data" envelope.
        JsonResource::withoutWrapping();
    }
}
