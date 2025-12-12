<div class="content">
    <div class="page-action">
        <form method="GET" action="{{ route('admin.leads.index') }}" style="display:inline-block;margin-right:10px; position:relative;">
            <input type="hidden" name="organization_id" value="{{ request('organization_id') }}" />
            <input type="text" name="organization_search" value="" placeholder="Buscar organización" autocomplete="off" />
            <ul id="org-suggestions" style="position:absolute; background:#1e1e1e; border:1px solid #444; list-style:none; margin:0; padding:0; max-height:200px; overflow:auto; width:300px; display:none;"></ul>
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
    <script>
    const input = document.querySelector('input[name="organization_search"]');
    const hidden = document.querySelector('input[name="organization_id"]');
    const list = document.getElementById('org-suggestions');
    let ctrl;
    input && input.addEventListener('input', async (e) => {
      const q = e.target.value.trim();
      hidden.value = '';
      if (ctrl) { ctrl.abort(); }
      if (!q) { list.style.display = 'none'; list.innerHTML = ''; return; }
      ctrl = new AbortController();
      try {
        const res = await fetch('{{ route('admin.organizations.search') }}?q=' + encodeURIComponent(q), { signal: ctrl.signal });
        const data = await res.json();
        list.innerHTML = '';
        data.forEach(item => {
          const li = document.createElement('li');
          li.textContent = item.name;
          li.style.padding = '6px 8px';
          li.style.cursor = 'pointer';
          li.addEventListener('click', () => {
            input.value = item.name;
            hidden.value = item.id;
            list.style.display = 'none';
            list.innerHTML = '';
          });
          list.appendChild(li);
        });
        list.style.display = data.length ? 'block' : 'none';
      } catch (err) {
        list.style.display = 'none';
        list.innerHTML = '';
      }
    });
    document.addEventListener('click', (e) => {
      if (!list.contains(e.target) && e.target !== input) {
        list.style.display = 'none';
      }
    });
    </script>
</div>
