document.addEventListener('DOMContentLoaded', () => {
    cargarRecursos();

    const form = document.getElementById('formRecurso');
    form.addEventListener('submit', e => {
        e.preventDefault();
        const datos = new FormData(form);
        datos.append('action', 'guardar');

        fetch('../backend/recursos_backend.php', {
            method: 'POST',
            body: datos
        })
        .then(r => r.json())
        .then(() => {
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('modalRecurso')).hide();
            cargarRecursos();
        });
    });
});

function cargarRecursos() {
    fetch('../backend/recursos_backend.php', {
        method: 'POST',
        body: new URLSearchParams({action: 'listar'})
    })
    .then(r => r.json())
    .then(data => {
        const tbody = document.querySelector('#tablaRecursos tbody');
        tbody.innerHTML = '';

        data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${r.id}</td>
                <td>${r.nombre}</td>
                <td>${r.tipo}</td>
                <td><span class="badge bg-${r.estado === 'Disponible' ? 'success' : r.estado === 'Ocupado' ? 'warning' : 'danger'}">${r.estado}</span></td>
                <td>${r.ubicacion ?? ''}</td>
                <td>${r.responsable ?? ''}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick='editar(${JSON.stringify(r)})'>✏️</button>
                    <button class="btn btn-sm btn-outline-danger" onclick='eliminar(${r.id})'>🗑️</button>
                </td>`;
            tbody.appendChild(tr);
        });
    });
}

function editar(r) {
    const form = document.getElementById('formRecurso');
    for (const [k,v] of Object.entries(r)) {
        if (form[k]) form[k].value = v;
    }
    new bootstrap.Modal('#modalRecurso').show();
}

function eliminar(id) {
    if (!confirm('¿Seguro que desea eliminar este recurso?')) return;
    const datos = new FormData();
    datos.append('action', 'eliminar');
    datos.append('id', id);

    fetch('../backend/recursos_backend.php', {
        method: 'POST',
        body: datos
    })
    .then(r => r.json())
    .then(() => cargarRecursos());
}
