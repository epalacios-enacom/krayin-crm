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

            // Debug logging
            $logPath = base_path('loglead.txt');
            $msg = "Booted EnacomLeadOrg at " . date('Y-m-d H:i:s') . "\n";

            $index = $routes->getByName('admin.leads.index');
            if ($index) {
                $msg .= "Found admin.leads.index. Old: " . $index->getActionName() . "\n";
                $index->uses(LeadOrgController::class . '@index');
                $msg .= "Updated admin.leads.index. New: " . $index->getActionName() . "\n";
            } else {
                $msg .= "admin.leads.index NOT FOUND\n";
            }

            $get = $routes->getByName('admin.leads.get');
            if ($get) {
                $msg .= "Found admin.leads.get. Old: " . $get->getActionName() . "\n";
                $get->uses(LeadOrgController::class . '@grid');
                $msg .= "Updated admin.leads.get. New: " . $get->getActionName() . "\n";
            } else {
                $msg .= "admin.leads.get NOT FOUND\n";
            }

            file_put_contents($logPath, $msg, FILE_APPEND);
        });
    }

    public function register()
    {
    }
}
