<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Chat;
use App\Models\Nation;
use App\Models\ShopItem;
use App\Policies\AnnouncementPolicy;
use App\Policies\ChatPolicy;
use App\Policies\NationPolicy;
use App\Policies\ShopItemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Nation::class => NationPolicy::class,
        Chat::class => ChatPolicy::class,
        Announcement::class => AnnouncementPolicy::class,
        ShopItem::class => ShopItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
