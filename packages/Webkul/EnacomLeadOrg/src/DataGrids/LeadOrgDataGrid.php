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
        parent::prepareQueryBuilder();

        $queryBuilder = $this->queryBuilder;

        $queryBuilder->leftJoin('persons as org_persons', 'leads.person_id', '=', 'org_persons.id')
            ->leftJoin('organizations', 'org_persons.organization_id', '=', 'organizations.id')
            ->addSelect('organizations.name as organization_name');

        $this->addFilter('organization_name', 'organizations.name');

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
