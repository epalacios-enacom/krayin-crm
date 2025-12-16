@extends('admin::layouts.app')

@section('page_title')
    Leads
@endsection

@section('content-wrapper')
    <div class="content">
        <div class="page-action">
            <form id="lead-filter-form" method="GET" action="{{ route('admin.leads.index') }}" style="display:inline-block;margin-right:10px; position:relative;">
                <input type="text" name="organization_search" value="" placeholder="Buscar organización" autocomplete="off" />
                <div id="org-selected" style="display:inline-block; margin-left:10px;"></div>
                <ul id="org-suggestions" style="display:none; position:absolute; background:#1e1e1e; border:1px solid #444; list-style:none; margin:0; padding:0; max-height:200px; overflow:auto; width:300px; z-index:1000;"></ul>
                <div id="org-hidden-filter"></div>
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.leads.index') }}" class="btn">Limpiar</a>
            </form>

            <form id="lead-export-form" method="GET" action="{{ route('admin.leads.export') }}" style="display:inline-block;">
                <div id="org-hidden-export"></div>
                <button type="submit" class="btn btn-primary">Exportar CSV</button>
            </form>
        </div>

        @php 
            $qs = http_build_query(['organization_ids' => request('organization_ids')]); 
            // Usamos la ruta estándar admin.leads.get, ya que el ServiceProvider inyecta nuestro Grid personalizado
            $srcUrl = route('admin.leads.get');
            if ($qs) {
                $srcUrl .= (strpos($srcUrl, '?') === false ? '?' : '&') . $qs;
            }
        @endphp

        <table-component 
            src="{{ $srcUrl }}" 
            :columns="[
                { name: 'id', label: 'ID' },
                { name: 'title', label: 'Título' },
                { name: 'organization_name', label: 'Empresa' },
                { name: 'created_at', label: 'Creado' }
            ]"
        ></table-component>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let selectedOrganizations = [];
            let organizationSearchInput = $('#lead-filter-form input[name="organization_search"]');
            let orgSuggestions = $('#org-suggestions');
            let orgSelectedDiv = $('#org-selected');
            let orgHiddenFilter = $('#org-hidden-filter');
            let orgHiddenExport = $('#org-hidden-export');

            // Initialize with existing organization_ids from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const initialOrgIds = urlParams.get('organization_ids');
            if (initialOrgIds) {
                selectedOrganizations = initialOrgIds.split(',').map(id => parseInt(id));
                updateSelectedOrganizationsDisplay();
            }

            organizationSearchInput.on('keyup', function() {
                let query = $(this).val();
                if (query.length < 2) {
                    orgSuggestions.hide();
                    return;
                }

                $.ajax({
                    url: '{{ route('admin.organizations.search') }}',
                    method: 'GET',
                    data: { query: query },
                    success: function(data) {
                        orgSuggestions.empty();
                        if (data.length > 0) {
                            $.each(data, function(index, org) {
                                if (!selectedOrganizations.includes(org.id)) {
                                    orgSuggestions.append(`<li data-id="${org.id}" data-name="${org.name}">${org.name}</li>`);
                                }
                            });
                            orgSuggestions.show();
                        } else {
                            orgSuggestions.hide();
                        }
                    }
                });
            });

            orgSuggestions.on('click', 'li', function() {
                let orgId = $(this).data('id');
                let orgName = $(this).data('name');

                if (!selectedOrganizations.includes(orgId)) {
                    selectedOrganizations.push(orgId);
                    updateSelectedOrganizationsDisplay();
                }

                organizationSearchInput.val('');
                orgSuggestions.hide();
            });

            orgSelectedDiv.on('click', '.selected-org', function() {
                let orgIdToRemove = $(this).data('id');
                selectedOrganizations = selectedOrganizations.filter(id => id !== orgIdToRemove);
                updateSelectedOrganizationsDisplay();
            });

            function updateSelectedOrganizationsDisplay() {
                orgSelectedDiv.empty();
                orgHiddenFilter.empty();
                orgHiddenExport.empty();

                if (selectedOrganizations.length > 0) {
                    selectedOrganizations.forEach(function(orgId) {
                        // Fetch organization name if not already known (e.g., on page load)
                        // For simplicity, assuming we have names or can fetch them.
                        // For now, just display ID if name is not readily available.
                        let orgName = $(`#org-suggestions li[data-id="${orgId}"]`).data('name') || `ID: ${orgId}`;
                        
                        // If the name is not found in suggestions (e.g., pre-selected from URL),
                        // we might need an AJAX call to get the name.
                        // For this example, we'll just show the ID if the name isn't in the current suggestions.
                        // A more robust solution would involve storing names or fetching them.
                        
                        orgSelectedDiv.append(`<span class="selected-org" data-id="${orgId}">${orgName} <i class="icon cross-icon"></i></span>`);
                        orgHiddenFilter.append(`<input type="hidden" name="organization_ids[]" value="${orgId}">`);
                        orgHiddenExport.append(`<input type="hidden" name="organization_ids[]" value="${orgId}">`);
                    });
                }
            }

            // Close suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#lead-filter-form').length) {
                    orgSuggestions.hide();
                }
            });
        });
    </script>
@endpush