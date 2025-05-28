<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\HandlerStack;
use App\Logging\GuzzleLogMiddleware;

use App\Models\User;
use App\Observers\UserObserver;

use App\Models\Product;
use App\Observers\ProductObserver;

use App\Models\Order;
use App\Observers\OrderObserver;

use App\Models\Review;
use App\Observers\ReviewObserver;

use App\Models\PromoCode;
use App\Observers\PromoCodeObserver;


class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {

        // build a handler stack once
        $stack = HandlerStack::create();
        $stack->push(new GuzzleLogMiddleware('api'));

        // override the default HTTP client to always use that stack
        Http::macro('withLogging', function () use ($stack) {
            return Http::withOptions(['handler' => $stack]);
        });

        User::observe(UserObserver::class);
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        Review::observe(ReviewObserver::class);
        PromoCode::observe(PromoCodeObserver::class);
    }
}
