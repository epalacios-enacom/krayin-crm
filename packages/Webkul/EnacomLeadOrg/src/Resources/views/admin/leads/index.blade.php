<div class="content">
    <div class="page-action">
        <form method="GET" action="{{ route('admin.leads.index') }}" style="display:inline-block;margin-right:10px;">
            <select name="organization_id">
                <option value="">Todas las organizaciones</option>
                @foreach($organizations as $org)
                    <option value="{{ $org->id }}" {{ (string)$selectedOrganizationId === (string)$org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('admin.leads.index') }}" class="btn">Limpiar</a>
        </form>
        <a href="{{ route('admin.leads.export', ['organization_id' => request('organization_id')]) }}" class="btn btn-primary">Exportar CSV</a>
    </div>
    <table-component
        src="{{ route('admin.leads.grid', ['organization_id' => request('organization_id')]) }}"
        :columns="[
            { name: 'id', label: 'ID' },
            { name: 'title', label: 'Título' },
            { name: 'organization_name', label: 'Organización' },
            { name: 'created_at', label: 'Creado' }
        ]"
    ></table-component>
</div>
