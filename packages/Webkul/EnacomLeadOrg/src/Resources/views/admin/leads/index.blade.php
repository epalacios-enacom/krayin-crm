<div class="content">
    <div class="page-action">
        <form method="GET" action="{{ route('admin.leads.index') }}" style="display:inline-block;margin-right:10px;">
            <input type="text" name="organization_name" value="{{ request('organization_name') }}" placeholder="Filtrar por Organización" />
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('admin.leads.index') }}" class="btn">Limpiar</a>
        </form>
        <a href="{{ route('admin.leads.export', ['organization_name' => request('organization_name')]) }}" class="btn btn-primary">Exportar CSV</a>
    </div>
    <table-component
        src="{{ route('admin.leads.grid', ['organization_name' => request('organization_name')]) }}"
        :columns="[
            { name: 'id', label: 'ID' },
            { name: 'title', label: 'Título' },
            { name: 'organization_name', label: 'Organización' },
            { name: 'created_at', label: 'Creado' }
        ]"
    ></table-component>
</div>
