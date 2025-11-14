document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const formAsistencia = document.getElementById('formAsistencia');
    const selectProfesor = document.getElementById('selectProfesor');
    const selectGrupo = document.getElementById('selectGrupo');
    const tablaAsistencias = document.getElementById('tablaAsistencias');
    
    // Cargar datos iniciales
    cargarHistorial();
    
    // Si es admin, cargar grupos cuando selecciona profesor
    if (rolUsuario === 'admin' && selectProfesor) {
        selectProfesor.addEventListener('change', function() {
            const profesorId = this.value;
            if (profesorId) {
                cargarGruposPorProfesor(profesorId);
            } else {
                selectGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
            }
        });
    } else {
        // Si es profesor, cargar sus grupos automáticamente
        cargarGruposPorProfesor(userId);
    }
    
    // Manejar envío del formulario
    if (formAsistencia) {
        formAsistencia.addEventListener('submit', function(e) {
            e.preventDefault();
            registrarAsistencia();
        });
    }
    
    // Función para cargar grupos por profesor
    function cargarGruposPorProfesor(profesorId) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/Agora/Agora/backend/procesar_asistencia.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success && response.grupos) {
                        selectGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
                        response.grupos.forEach(grupo => {
                            const option = document.createElement('option');
                            option.value = grupo.id;
                            option.textContent = grupo.nombre_grupo;
                            selectGrupo.appendChild(option);
                        });
                    } else {
                        selectGrupo.innerHTML = '<option value="">No hay grupos</option>';
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    selectGrupo.innerHTML = '<option value="">Error cargando grupos</option>';
                }
            }
        };
        
        xhr.send('action=cargar_grupos&profesor_id=' + encodeURIComponent(profesorId));
    }
    
    // Función para registrar asistencia
    function registrarAsistencia() {
        const formData = new FormData(formAsistencia);
        
        // Validación básica
        const requiredFields = ['usuario_id', 'fecha', 'grupo_id', 'bloque_id', 'estado'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!formData.get(field)) {
                isValid = false;
                mostrarAlerta('Por favor, complete todos los campos obligatorios', 'warning');
            }
        });
        
        if (!isValid) return;
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/Agora/Agora/backend/procesar_asistencia.php', true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    if (response.success) {
                        mostrarAlerta('Asistencia registrada correctamente', 'success');
                        formAsistencia.reset();
                        document.getElementById('fechaInput').value = new Date().toISOString().split('T')[0];
                        cargarHistorial();
                    } else {
                        mostrarAlerta(response.message || 'Error al registrar asistencia', 'danger');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    mostrarAlerta('Error en la respuesta del servidor', 'danger');
                }
            } else {
                mostrarAlerta('Error de conexión', 'danger');
            }
        };
        
        xhr.send(formData);
    }
    
    // Función para cargar historial
    function cargarHistorial() {
        const profesorId = rolUsuario === 'admin' ? (selectProfesor ? selectProfesor.value : '') : userId;
        
        if (rolUsuario === 'admin' && !profesorId) {
            tablaAsistencias.innerHTML = '<div class="alert alert-info">Seleccione un profesor para ver su historial</div>';
            return;
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/Agora/Agora/backend/procesar_asistencia.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    if (response.success && response.historial) {
                        renderizarHistorial(response.historial);
                    } else {
                        tablaAsistencias.innerHTML = '<div class="alert alert-info">No hay registros de asistencia</div>';
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    tablaAsistencias.innerHTML = '<div class="alert alert-danger">Error cargando historial</div>';
                }
            }
        };
        
        xhr.send('action=cargar_historial&profesor_id=' + encodeURIComponent(profesorId));
    }
    
    // Función para renderizar el historial
    function renderizarHistorial(historial) {
        let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Grupo</th>
                            <th>Bloque</th>
                            <th>Salón</th>
                            <th>Estado</th>
                            <th>Justificación</th>
                            <th>Hora Registro</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        historial.forEach(asistencia => {
            const estadoBadge = asistencia.estado === 'asistio' 
                ? '<span class="badge bg-success">Asistió</span>'
                : '<span class="badge bg-danger">No asistió</span>';
                
            html += `
                <tr>
                    <td>${asistencia.fecha}</td>
                    <td>${asistencia.nombre_grupo || 'N/A'}</td>
                    <td>${asistencia.nombre_bloque || 'N/A'}</td>
                    <td>${asistencia.nombre_salon || 'N/A'}</td>
                    <td>${estadoBadge}</td>
                    <td>${asistencia.justificacion || '-'}</td>
                    <td>${asistencia.hora_registro || '-'}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaAsistencias.innerHTML = html;
    }
    
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo) {
        // Remover alertas existentes
        const alertasExistentes = document.querySelectorAll('.alert-flotante');
        alertasExistentes.forEach(alerta => alerta.remove());
        
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show alert-flotante`;
        alerta.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alerta);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 5000);
    }
});