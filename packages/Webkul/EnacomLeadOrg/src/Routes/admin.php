<?php

use Illuminate\Support\Facades\Route;
use Webkul\EnacomLeadOrg\Http\Controllers\Admin\LeadOrgController;

Route::group(['middleware' => ['web', 'admin']], function () {
    Route::get('admin/leads-enacom', [LeadOrgController::class, 'index'])->name('admin.leads.enacom.index');
    Route::get('admin/leads-enacom/grid', [LeadOrgController::class, 'grid'])->name('admin.leads.enacom.grid');
    Route::get('admin/leads-enacom/export', [LeadOrgController::class, 'export'])->name('admin.leads.enacom.export');
});

