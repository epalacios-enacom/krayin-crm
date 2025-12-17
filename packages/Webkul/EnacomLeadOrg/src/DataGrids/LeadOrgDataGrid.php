<?php

namespace Webkul\EnacomLeadOrg\DataGrids;

use Webkul\DataGrid\DataGrid;
use Illuminate\Support\Facades\DB;

class LeadOrgDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('leads')
            ->select(
                'leads.id',
                'leads.title',
                'leads.status',
                'leads.lead_value',
                'leads.created_at',
                'leads.user_id',
                'leads.person_id',
                'leads.lead_pipeline_stage_id'
            )
            ->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
            ->leftJoin('users', 'leads.user_id', '=', 'users.id')
            ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
            ->leftJoin('organizations', 'persons.organization_id', '=', 'organizations.id')
            ->addSelect(
                'persons.name as person_name',
                'users.name as user_name',
                'lead_pipeline_stages.name as stage_name',
                'organizations.name as organization_name'
            );

        $this->addFilter('id', 'leads.id');
        $this->addFilter('title', 'leads.title');
        $this->addFilter('person_name', 'persons.name');
        $this->addFilter('user_name', 'users.name');
        $this->addFilter('stage_name', 'lead_pipeline_stages.name');
        $this->addFilter('organization_name', 'organizations.name');
        $this->addFilter('created_at', 'leads.created_at');

        $this->setQueryBuilder($queryBuilder);
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.datagrid.id'),
            'type' => 'integer',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'title',
            'label' => trans('admin::app.datagrid.title'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'person_name',
            'label' => trans('admin::app.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'organization_name',
            'label' => 'Empresa',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'stage_name',
            'label' => trans('admin::app.datagrid.stage'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.datagrid.created_at'),
            'type' => 'datetime',
            'sortable' => true,
            'filterable' => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'title' => trans('admin::app.datagrid.view'),
            'method' => 'GET',
            'route' => 'admin.leads.view',
            'icon' => 'icon-eye',
            'url' => function ($row) {
                return route('admin.leads.view', $row->id);
            },
        ]);

        $this->addAction([
            'title' => trans('admin::app.datagrid.delete'),
            'method' => 'DELETE',
            'route' => 'admin.leads.delete',
            'icon' => 'icon-delete',
            'url' => function ($row) {
                return route('admin.leads.delete', $row->id);
            },
        ]);
    }
}
