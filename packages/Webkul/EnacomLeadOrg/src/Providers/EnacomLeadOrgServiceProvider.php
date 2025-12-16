<?php

namespace Webkul\EnacomLeadOrg\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

class EnacomLeadOrgServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'enacomleadorg');

        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');

            $routes = \Illuminate\Support\Facades\Route::getRoutes();

            $index = $routes->getByName('admin.leads.index');
            if ($index) {
                // Aggressively set the action
                $action = $index->getAction();
                $action['uses'] = LeadOrgController::class . '@index';
                $action['controller'] = LeadOrgController::class . '@index';
                $index->setAction($action);
            }

            // Removed hijacking of admin.leads.get as we now use admin.leads.enacom_grid directly in the View.
        });
    }

    public function register()
    {
    }
}
