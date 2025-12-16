<?php

use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

Route::group(['middleware' => ['web']], function () {
    Route::get('admin/enacom-test', function() { return 'ENACOM PACKAGE IS ACTIVE'; });
    Route::get('admin/leads/get/{pipeline_id?}', [LeadOrgController::class, 'grid'])->name('admin.leads.get');
    Route::get('admin/leads/grid', [LeadOrgController::class, 'grid'])->name('admin.leads.grid');
    Route::get('admin/leads/export', [LeadOrgController::class, 'export'])->name('admin.leads.export');
    Route::get('admin/organizations/search', [\Webkul\EnacomLeadOrg\Http\Controllers\Admin\OrganizationLookupController::class, 'search'])->name('admin.organizations.search');

    Route::get('admin/override-check', function() {
        $routes = Route::getRoutes();
        $index = $routes->getByName('admin.leads.index');
        $get = $routes->getByName('admin.leads.get');
        return response()->json([
            'index_uses' => $index ? ($index->getActionName() ?? 'null') : 'missing',
            'get_uses' => $get ? ($get->getActionName() ?? 'null') : 'missing',
        ]);
    });
});
