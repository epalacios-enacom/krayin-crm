<div class="content">
    <table-component
        src="{{ route('admin.leads.grid') }}"
        :columns="[
            { name: 'id', label: 'ID' },
            { name: 'title', label: 'Título' },
            { name: 'organization_name', label: 'Organización' },
            { name: 'created_at', label: 'Creado' }
        ]"
    ></table-component>

    <a href="{{ route('admin.leads.export') }}" class="btn btn-primary">Exportar CSV</a>
</div>
