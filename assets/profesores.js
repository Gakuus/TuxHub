document.addEventListener('DOMContentLoaded', function() {
    const selectProfesor = document.getElementById('selectProfesor');
    const selectGrupo = document.getElementById('selectGrupo');
    const selectBloque = document.getElementById('selectBloque');
    const fechaInput = document.getElementById('fechaInput');
    const formAsistencia = document.getElementById('formAsistencia');

    // Ruta absoluta correcta
    const backendPath = '/Agora/Agora/backend/horarios_profesores.php';
    console.log('Ruta backend:', backendPath);

    // Event listeners
    if (selectProfesor && selectProfesor.tagName === 'SELECT') {
        selectProfesor.addEventListener('change', function() {
            console.log('Profesor cambiado:', this.value);
            cargarGruposYBloques();
        });
    }

    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            console.log('Fecha cambiada:', this.value);
            cargarGruposYBloques();
        });
    }

    // Cargar inicialmente para profesores
    if (rolUsuario === 'profesor') {
        console.log('Cargando inicialmente para profesor');
        setTimeout(() => {
            cargarGruposYBloques();
        }, 100);
    }

    // Para admin, cargar cuando seleccione un profesor
    if (rolUsuario === 'admin' && selectProfesor) {
        // Cargar cuando ya haya un profesor seleccionado por defecto
        if (selectProfesor.value) {
            setTimeout(() => {
                cargarGruposYBloques();
            }, 500);
        }
    }

    formAsistencia.addEventListener('submit', guardarAsistencia);
    
    // Cargar historial
    cargarHistorialAsistencias();

    function cargarGruposYBloques() {
        const profesorId = rolUsuario === 'admin' ? selectProfesor.value : userId;
        const fecha = fechaInput ? fechaInput.value : new Date().toISOString().split('T')[0];
        
        console.log('cargarGruposYBloques - Profesor ID:', profesorId, 'Fecha:', fecha);

        if (!profesorId) {
            console.log('No hay profesor ID seleccionado');
            selectGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
            selectBloque.innerHTML = '<option value="">Seleccionar bloque</option>';
            return;
        }

        // Cargar grupos
        console.log('Cargando grupos...');
        fetch(`${backendPath}?action=grupos&profesor_id=${profesorId}`)
            .then(response => {
                console.log('Respuesta grupos status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos grupos recibidos:', data);
                selectGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
                if (data.success && data.grupos && data.grupos.length > 0) {
                    data.grupos.forEach(grupo => {
                        const option = document.createElement('option');
                        option.value = grupo.id;
                        option.textContent = grupo.nombre_grupo;
                        selectGrupo.appendChild(option);
                    });
                    console.log('Grupos cargados:', data.grupos.length);
                } else {
                    console.log('No se encontraron grupos o error en la respuesta');
                    selectGrupo.innerHTML = '<option value="">No hay grupos disponibles</option>';
                }
            })
            .catch(error => {
                console.error('Error cargando grupos:', error);
                selectGrupo.innerHTML = '<option value="">Error cargando grupos: ' + error.message + '</option>';
            });

        // Cargar bloques horarios
        console.log('Cargando bloques...');
        fetch(`${backendPath}?action=bloques&profesor_id=${profesorId}&fecha=${fecha}`)
            .then(response => {
                console.log('Respuesta bloques status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos bloques recibidos:', data);
                selectBloque.innerHTML = '<option value="">Seleccionar bloque</option>';
                if (data.success && data.bloques && data.bloques.length > 0) {
                    data.bloques.forEach(bloque => {
                        const option = document.createElement('option');
                        option.value = bloque.id;
                        option.textContent = `${bloque.nombre_bloque} (${bloque.nombre_dia} ${bloque.hora_inicio}-${bloque.hora_fin}) - ${bloque.nombre_grupo}`;
                        option.setAttribute('data-grupo-id', bloque.grupo_id);
                        selectBloque.appendChild(option);
                    });
                    console.log('Bloques cargados:', data.bloques.length);
                } else {
                    console.log('No se encontraron bloques para esta fecha');
                    selectBloque.innerHTML = '<option value="">No hay bloques para esta fecha</option>';
                }
            })
            .catch(error => {
                console.error('Error cargando bloques:', error);
                selectBloque.innerHTML = '<option value="">Error cargando bloques: ' + error.message + '</option>';
            });
    }

    // Sincronizar grupo cuando se selecciona bloque
    selectBloque.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const grupoId = selectedOption.getAttribute('data-grupo-id');
        if (grupoId && selectGrupo) {
            selectGrupo.value = grupoId;
            console.log('Grupo sincronizado:', grupoId);
        }
    });

    function guardarAsistencia(e) {
        e.preventDefault();
        console.log('Enviando formulario...');
        
        const formData = new FormData(formAsistencia);
        
        fetch(backendPath, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                alert('Asistencia registrada correctamente');
                formAsistencia.reset();
                if (fechaInput) {
                    fechaInput.value = new Date().toISOString().split('T')[0];
                }
                cargarHistorialAsistencias();
                
                // Recargar los selects
                cargarGruposYBloques();
            } else {
                alert('Error: ' + (data.message || 'No se pudo registrar la asistencia'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al registrar asistencia: ' + error.message);
        });
    }

    function cargarHistorialAsistencias() {
        console.log('Cargando historial...');
        const params = new URLSearchParams();
        if (rolUsuario === 'admin') {
            if (selectProfesor && selectProfesor.value) {
                params.append('profesor_id', selectProfesor.value);
            }
        } else {
            params.append('profesor_id', userId);
        }

        fetch(`${backendPath}?action=historial&${params}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos historial recibidos:', data);
                const tabla = document.getElementById('tablaAsistencias');
                if (data.success && data.asistencias && data.asistencias.length > 0) {
                    tabla.innerHTML = generarTablaHistorial(data.asistencias);
                } else {
                    tabla.innerHTML = '<div class="alert alert-info">No hay registros de asistencia</div>';
                }
            })
            .catch(error => {
                console.error('Error cargando historial:', error);
                document.getElementById('tablaAsistencias').innerHTML = 'Error cargando historial: ' + error.message;
            });
    }

    function generarTablaHistorial(asistencias) {
        let html = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Profesor</th>
                            <th>Grupo</th>
                            <th>Bloque Horario</th>
                            <th>Salón</th>
                            <th>Estado</th>
                            <th>Justificación</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        asistencias.forEach(asistencia => {
            html += `
                <tr>
                    <td>${asistencia.fecha}</td>
                    <td>${asistencia.nombre_profesor}</td>
                    <td>${asistencia.nombre_grupo}</td>
                    <td>${asistencia.bloque_info}</td>
                    <td>${asistencia.nombre_salon || '-'}</td>
                    <td><span class="badge ${asistencia.estado === 'asistio' ? 'bg-success' : 'bg-danger'}">${asistencia.estado}</span></td>
                    <td>${asistencia.justificacion || '-'}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        return html;
    }
});