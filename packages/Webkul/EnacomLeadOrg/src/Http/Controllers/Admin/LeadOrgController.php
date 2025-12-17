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

        if ($request->input('view_type') === 'board') {
            $orgIds = (array) $request->input('organization_ids', []);
            $orgIds = array_values(array_filter($orgIds));

            $query = DB::table('leads')
                ->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
                ->leftJoin('organizations', 'persons.organization_id', '=', 'organizations.id')
                ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
                ->select(
                    'leads.id',
                    'leads.title',
                    'leads.lead_pipeline_stage_id',
                    DB::raw('COALESCE(organizations.name, "") as organization_name'),
                    DB::raw('COALESCE(lead_pipeline_stages.name, "Sin etapa") as stage_name')
                );

            if (count($orgIds)) {
                $query->whereIn('persons.organization_id', $orgIds);
            }

            $leads = $query->orderBy('lead_pipeline_stages.sort_order')->get();

            $columns = [];
            foreach ($leads as $lead) {
                $columns[$lead->stage_name][] = $lead;
            }

            return view('enacomleadorg::admin.leads.board', [
                'organizations' => $organizations,
                'selectedOrganizationIds' => $orgIds,
                'columns' => $columns,
            ]);
        }

        // Fetch current pipeline (required by admin::leads.index view)
        $pipelineId = $request->input('pipeline_id');
        $pipeline = null;

        if ($pipelineId) {
            $pipeline = DB::table('lead_pipelines')->where('id', $pipelineId)->first();
        }

        if (!$pipeline) {
            $pipeline = DB::table('lead_pipelines')->where('is_default', 1)->first();
        }

        // Manually hydrate stages for the view to prevent "Undefined property: stdClass::$stages"
        if ($pipeline) {
            $pipeline->stages = DB::table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $pipeline->id)
                ->orderBy('sort_order', 'asc')
                ->get();
        }

        // Return standard view to avoid layout issues. 
        // We pass empty columns to satisfy the Kanban view requirement, 
        // even though this controller is primarily for the custom Grid.
        if ($request->ajax()) {
            return app(LeadOrgDataGrid::class)->toJson();
        }

        return view('admin::leads.index', compact('pipeline') + ['columns' => []]);
    }

    public function grid(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('LeadOrgController: grid action hit for JSON request.');
            $grid = new LeadOrgDataGrid;
            $json = $grid->toJson();
            \Illuminate\Support\Facades\Log::info('LeadOrgController: grid JSON generated. Length: ' . strlen($json->content()));
            return $json;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LeadOrgController: Error generating grid JSON: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

        $orgIds = (array) $request->input('organization_ids', []);
        $orgIds = array_values(array_filter($orgIds));
        if (count($orgIds)) {
            $query->whereIn('persons.organization_id', $orgIds);
        } else {
            $orgId = $request->input('organization_id');
            if ($orgId) {
                $query->where('persons.organization_id', $orgId);
            }
        }

        $org = $request->input('organization_name');
        if ($org) {
            $query->where('organizations.name', 'like', '%' . $org . '%');
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
