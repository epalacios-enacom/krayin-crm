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
        $queryBuilder = parent::prepareQueryBuilder();

        $queryBuilder->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
            ->leftJoin('organizations', 'persons.organization_id', '=', 'organizations.id')
            ->addSelect('organizations.name as organization_name');

        $this->addFilter('organization_name', 'organizations.name');

        $this->setQueryBuilder($queryBuilder);
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function addColumns()
    {
        parent::addColumns();

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
