<?php

namespace Webkul\EnacomLeadOrg\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

class EnacomLeadOrgServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'enacomleadorg');

        $this->app->booted(function () {
            $route = Route::getRoutes()->getByName('admin.leads.index');

            if ($route) {
                $route->uses(LeadOrgController::class . '@index');
            }
        });
    }

    public function register()
    {
    }
}
