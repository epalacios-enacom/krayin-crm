<?php

namespace Webkul\EnacomLeadOrg\DataGrids;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Webkul\DataGrid\DataGrid;

class LeadOrgDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('leads')
            ->addSelect(
                'leads.id',
                'leads.title',
                'leads.lead_value',
                'leads.status',
                'leads.created_at',
                'leads.expected_close_date',
                'users.name as sales_person',
                'persons.name as contact_person',
                'organizations.name as organization_name',
                'lead_sources.name as source',
                'lead_types.name as type',
                'lead_pipeline_stages.name as stage'
            )
            ->leftJoin('users', 'leads.user_id', '=', 'users.id')
            ->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
            ->leftJoin('organizations', 'persons.organization_id', '=', 'organizations.id')
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->leftJoin('lead_types', 'leads.lead_type_id', '=', 'lead_types.id')
            ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id');

        $this->addFilter('id', 'leads.id');
        $this->addFilter('sales_person', 'users.name');
        $this->addFilter('contact_person', 'persons.name');
        $this->addFilter('organization_name', 'organizations.name');
        $this->addFilter('title', 'leads.title');

        $this->setQueryBuilder($queryBuilder);
        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.datagrid.id'),
            'type'       => 'string',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'sales_person',
            'label'      => trans('admin::app.datagrid.sales-person'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'title',
            'label'      => trans('admin::app.datagrid.subject'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'filterable' => true,
        ]);
        
        $this->addColumn([
            'index'      => 'organization_name',
            'label'      => 'Empresa',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'filterable' => true,
            'visibility' => true,
        ]);

        $this->addColumn([
            'index'      => 'source',
            'label'      => trans('admin::app.datagrid.source'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'lead_value',
            'label'      => trans('admin::app.datagrid.lead_value'),
            'type'       => 'string',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.datagrid.type'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_person',
            'label'      => trans('admin::app.datagrid.contact_person'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'stage',
            'label'      => trans('admin::app.datagrid.stage'),
            'type'       => 'string',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'expected_close_date',
            'label'      => trans('admin::app.datagrid.expected_close_date'),
            'type'       => 'date',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.datagrid.created_at'),
            'type'       => 'date',
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        return;
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        $this->addMassAction([
            'type'   => 'delete',
            'title'  => trans('ui::app.datagrid.actions.delete'),
            'url'    => route('admin.leads.mass_delete'),
            'method' => 'POST',
        ]);
    }
}
