<?php

namespace Webkul\EnacomLeadOrg\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\DataGrids\LeadDataGrid as BaseLeadDataGrid;

class LeadOrgDataGrid extends BaseLeadDataGrid
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
        parent::prepareColumns();

        $this->addColumn([
            'index' => 'organization_name',
            'label' => 'Empresa',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);
    }
}
