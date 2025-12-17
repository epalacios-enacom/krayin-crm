<?php

use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

Route::group(['middleware' => ['web', 'user']], function () {
    Route::get('admin/enacom-test', function () {
        return 'ENACOM PACKAGE IS ACTIVE';
    });

    // Custom grid route to avoid conflict with core admin.leads.get
    Route::get('admin/leads/enacom-grid/{pipeline_id?}', [LeadOrgController::class, 'grid'])->name('admin.leads.enacom_grid');

    // Explicitly override the core routes by defining them after the core routes (assuming this package loads after or late enough)
    // or relying on Laravel's behavior of last route definition winning for same URI.

    Route::get('admin/leads', [LeadOrgController::class, 'index'])->name('admin.leads.index');
    Route::get('admin/leads/get', [LeadOrgController::class, 'grid'])->name('admin.leads.get');

    Route::get('admin/leads/export', [LeadOrgController::class, 'export'])->name('admin.leads.export');
    Route::get('admin/organizations/search', [\Webkul\EnacomLeadOrg\Http\Controllers\Admin\OrganizationLookupController::class, 'search'])->name('admin.organizations.search');

    Route::get('admin/override-check', function () {
        $routes = Route::getRoutes();
        $index = $routes->getByName('admin.leads.index');
        $get = $routes->getByName('admin.leads.get');
        return response()->json([
            'index_uses' => $index ? ($index->getActionName() ?? 'null') : 'missing',
            'get_uses' => $get ? ($get->getActionName() ?? 'null') : 'missing',
        ]);
    });
});
