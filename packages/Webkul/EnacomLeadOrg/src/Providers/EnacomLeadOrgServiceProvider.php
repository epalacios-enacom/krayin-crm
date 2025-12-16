<?php

namespace Webkul\EnacomLeadOrg\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

class EnacomLeadOrgServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'enacomleadorg');

        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        });
    }

    public function register()
    {
    }
}
