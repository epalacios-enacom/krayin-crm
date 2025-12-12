<?php

namespace Webkul\EnacomLeadOrg\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Webkul\EnacomLeadOrg\DataGrids\LeadOrgDataGrid;

class LeadOrgController
{
    public function index(Request $request)
    {
        $organizations = DB::table('organizations')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('enacomleadorg::admin.leads.index', [
            'organizations' => $organizations,
            'selectedOrganizationId' => $request->input('organization_id'),
        ]);
    }

    public function grid(Request $request)
    {
        $grid = new LeadOrgDataGrid;
        return $grid->toJson();
    }

    public function export(Request $request)
    {
        $grid = new LeadOrgDataGrid;
        $grid->prepareQueryBuilder();
        $query = $grid->getQueryBuilder();

        $ids = $request->input('ids');
        if (is_array($ids) && count($ids)) {
            $query->whereIn('leads.id', $ids);
        }

        $orgId = $request->input('organization_id');
        if ($orgId) {
            $query->where('leads.organization_id', $orgId);
        }

        $org = $request->input('organization_name');
        if ($org) {
            $query->where('organizations.name', 'like', '%'.$org.'%');
        }
        $rows = $query->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_enacom.csv"',
        ];
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Título', 'Organización', 'Creado']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->title,
                    $row->organization_name,
                    $row->created_at,
                ]);
            }
            fclose($out);
        };
        return Response::stream($callback, 200, $headers);
    }
}
