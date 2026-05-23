document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAsistencia');
    const selectProfesor = document.getElementById('selectProfesor');
    const selectGrupo = document.getElementById('selectGrupo');
    const tabla = document.getElementById('tablaAsistencias');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content');
        const input = document.querySelector('#formAsistencia [name="csrf_token"]');
        return input ? input.value : '';
    }

    function cargarGrupos(profesorId) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'backend/procesar_asistencia.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success && res.grupos) {
                        selectGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
                        res.grupos.forEach(g => {
                            selectGrupo.innerHTML += `<option value="${g.id}">${g.nombre_grupo}</option>`;
                        });
                    } else {
                        selectGrupo.innerHTML = '<option value="">No hay grupos disponibles</option>';
                    }
                } catch (e) {
                    selectGrupo.innerHTML = '<option value="">Error al cargar grupos</option>';
                }
            }
        };
        xhr.send('action=cargar_grupos&profesor_id=' + encodeURIComponent(profesorId) + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

    function cargarHistorial() {
        const profesorId = selectProfesor ? selectProfesor.value : (window.userId || '');
        if (!profesorId && tabla) {
            tabla.innerHTML = '<div class="alert alert-info">Seleccione un profesor.</div>';
            return;
        }
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'backend/procesar_asistencia.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = () => {
            if (xhr.status === 200 && tabla) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success && res.historial) {
                        let html = '<div class="table-responsive"><table class="table table-striped"><thead class="table-dark"><tr><th>Fecha</th><th>Grupo</th><th>Bloque</th><th>Salón</th><th>Estado</th><th>Justificación</th></tr></thead><tbody>';
                        res.historial.forEach(a => {
                            const badge = a.estado === 'asistio' ? '<span class="badge bg-success">Asistió</span>' : '<span class="badge bg-danger">No asistió</span>';
                            html += `<tr><td>${a.fecha}</td><td>${a.nombre_grupo || 'N/A'}</td><td>${a.nombre_bloque || 'N/A'}</td><td>${a.nombre_salon || 'N/A'}</td><td>${badge}</td><td>${a.justificacion || '-'}</td></tr>`;
                        });
                        html += '</tbody></table></div>';
                        tabla.innerHTML = html;
                    } else {
                        tabla.innerHTML = '<div class="alert alert-info">No hay registros.</div>';
                    }
                } catch (e) {
                    tabla.innerHTML = '<div class="alert alert-danger">Error al cargar historial.</div>';
                }
            }
        };
        xhr.send('action=cargar_historial&profesor_id=' + encodeURIComponent(profesorId) + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

    if (selectProfesor) {
        selectProfesor.addEventListener('change', () => {
            if (selectProfesor.value) {
                cargarGrupos(selectProfesor.value);
                cargarHistorial();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const data = new FormData(form);
            const required = ['usuario_id', 'fecha', 'grupo_id', 'bloque_id', 'estado'];
            let valid = true;
            required.forEach(f => {
                if (!data.get(f)) {
                    valid = false;
                }
            });
            if (!valid) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.warning('Validación', 'Complete todos los campos obligatorios.');
                }
                return;
            }
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/procesar_asistencia.php', true);
            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            if (typeof ToastSystem !== 'undefined') {
                                ToastSystem.success('Asistencia registrada', 'El registro se guardó correctamente.');
                            }
                            form.reset();
                            if (document.getElementById('fechaInput')) {
                                document.getElementById('fechaInput').value = new Date().toISOString().split('T')[0];
                            }
                            cargarHistorial();
                        } else {
                            if (typeof ToastSystem !== 'undefined') {
                                ToastSystem.error('Error', res.message || 'No se pudo registrar la asistencia.');
                            }
                        }
                    } catch (e) {
                        if (typeof ToastSystem !== 'undefined') {
                            ToastSystem.error('Error', 'Respuesta inválida del servidor.');
                        }
                    }
                } else {
                    if (typeof ToastSystem !== 'undefined') {
                        ToastSystem.error('Error de conexión', 'No se pudo conectar con el servidor.');
                    }
                }
            };
            xhr.send(data);
        });
    }

    if (selectProfesor && selectProfesor.value) {
        cargarGrupos(selectProfesor.value);
    }
    cargarHistorial();
});
