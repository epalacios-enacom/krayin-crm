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
                $action = $index->getAction();
                $action['uses'] = LeadOrgController::class . '@index';
                $action['controller'] = LeadOrgController::class . '@index';
                $index->setAction($action);
            }

            // Override the AJAX route that provides the JSON data
            $get = $routes->getByName('admin.leads.get');
            if ($get) {
                $action = $get->getAction();
                $action['uses'] = LeadOrgController::class . '@grid';
                $action['controller'] = LeadOrgController::class . '@grid';
                $get->setAction($action);
            }
        });
    }

    public function register()
    {
        $this->app->bind(
            \Webkul\Admin\DataGrids\LeadDataGrid::class,
            \Webkul\EnacomLeadOrg\DataGrids\LeadOrgDataGrid::class
        );
    }
}
