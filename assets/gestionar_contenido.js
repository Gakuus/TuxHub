// Funcionalidades específicas para el panel de gestión de contenido

document.addEventListener('DOMContentLoaded', function() {
    inicializarContadores();
    inicializarValidaciones();
    inicializarAnimaciones();
});

// Inicializar contadores de caracteres
function inicializarContadores() {
    // Contador para título
    const tituloInput = document.querySelector('input[name="titulo"]');
    const tituloCounter = document.getElementById('titulo-counter');
    
    if (tituloInput && tituloCounter) {
        tituloCounter.textContent = tituloInput.value.length;
        tituloInput.addEventListener('input', function() {
            tituloCounter.textContent = this.value.length;
            actualizarEstadoContador(tituloCounter, this.value.length, 24);
        });
        
        // Estado inicial
        actualizarEstadoContador(tituloCounter, tituloInput.value.length, 24);
    }

    // Contador para contenido o mensaje
    const contenidoInput = document.querySelector('textarea[name="contenido"]') || document.querySelector('textarea[name="mensaje"]');
    const contenidoCounter = document.getElementById('contenido-counter') || document.getElementById('mensaje-counter');
    
    if (contenidoInput && contenidoCounter) {
        contenidoCounter.textContent = contenidoInput.value.length;
        contenidoInput.addEventListener('input', function() {
            contenidoCounter.textContent = this.value.length;
            actualizarEstadoContador(contenidoCounter, this.value.length, 255);
        });
        
        // Estado inicial
        actualizarEstadoContador(contenidoCounter, contenidoInput.value.length, 255);
    }
}

// Actualizar el estado visual del contador
function actualizarEstadoContador(contador, longitud, maximo) {
    contador.classList.remove('text-success', 'text-warning', 'text-danger');
    
    if (longitud === 0) {
        contador.classList.add('text-muted');
    } else if (longitud <= maximo * 0.8) {
        contador.classList.add('text-success');
    } else if (longitud <= maximo * 0.95) {
        contador.classList.add('text-warning');
    } else {
        contador.classList.add('text-danger');
    }
}

// Validación de imagen
function validateImage(input) {
    const file = input.files[0];
    if (file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validar tipo de archivo
        if (!validTypes.includes(file.type)) {
            mostrarAlerta('Por favor, selecciona solo archivos de imagen (JPG, PNG, GIF, WebP).', 'error');
            input.value = '';
            return false;
        }
        
        // Validar tamaño
        if (file.size > maxSize) {
            mostrarAlerta('La imagen no debe superar los 5MB.', 'error');
            input.value = '';
            return false;
        }
        
        // Mostrar vista previa si es una nueva imagen
        if (!input.hasAttribute('data-existing')) {
            mostrarVistaPrevia(file);
        }
        
        mostrarAlerta('Imagen válida. Puedes guardar el formulario.', 'success');
    }
    return true;
}

// Mostrar vista previa de imagen
function mostrarVistaPrevia(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        // Buscar contenedor de vista previa existente o crear uno nuevo
        let previewContainer = document.getElementById('image-preview-container');
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.id = 'image-preview-container';
            previewContainer.className = 'mb-3';
            
            const label = document.createElement('p');
            label.textContent = 'Vista previa:';
            previewContainer.appendChild(label);
            
            const inputFile = document.querySelector('input[name="imagen_file"]');
            inputFile.parentNode.insertBefore(previewContainer, inputFile.nextSibling);
        } else {
            // Limpiar vista previa anterior
            const oldImg = previewContainer.querySelector('img');
            if (oldImg) oldImg.remove();
        }
        
        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = 'Vista previa';
        img.className = 'img-fluid rounded shadow-sm mt-2';
        img.style.maxHeight = '150px';
        
        previewContainer.appendChild(img);
    };
    reader.readAsDataURL(file);
}

// Mostrar alertas personalizadas
function mostrarAlerta(mensaje, tipo) {
    // Remover alertas existentes
    const alertasExistentes = document.querySelectorAll('.alert-dismissible.custom-alert');
    alertasExistentes.forEach(alerta => alerta.remove());
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show mt-3 custom-alerta`;
    alerta.innerHTML = `
        <i class="bi bi-${tipo === 'error' ? 'exclamation-triangle' : tipo === 'success' ? 'check-circle' : 'info-circle'}"></i> 
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const formulario = document.querySelector('form');
    formulario.parentNode.insertBefore(alerta, formulario);
    
    // Auto-eliminar después de 5 segundos para alertas de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 5000);
    }
}

// Inicializar validaciones de formulario
function inicializarValidaciones() {
    const formulario = document.querySelector('form');
    
    formulario.addEventListener('submit', function(e) {
        let esValido = true;
        
        // Validar título
        const tituloInput = document.querySelector('input[name="titulo"]');
        if (tituloInput && tituloInput.value.trim().length === 0) {
            marcarInvalido(tituloInput, 'El título es obligatorio');
            esValido = false;
        } else if (tituloInput && tituloInput.value.length > 24) {
            marcarInvalido(tituloInput, 'El título no puede tener más de 24 caracteres');
            esValido = false;
        } else {
            marcarValido(tituloInput);
        }
        
        // Validar contenido/mensaje
        const contenidoInput = document.querySelector('textarea[name="contenido"]') || document.querySelector('textarea[name="mensaje"]');
        if (contenidoInput && contenidoInput.value.trim().length === 0) {
            marcarInvalido(contenidoInput, 'Este campo es obligatorio');
            esValido = false;
        } else if (contenidoInput && contenidoInput.value.length > 255) {
            marcarInvalido(contenidoInput, 'No puede tener más de 255 caracteres');
            esValido = false;
        } else {
            marcarValido(contenidoInput);
        }
        
        if (!esValido) {
            e.preventDefault();
            mostrarAlerta('Por favor, corrige los errores en el formulario.', 'error');
        }
    });
}

// Marcar campo como inválido
function marcarInvalido(campo, mensaje) {
    campo.classList.add('is-invalid');
    campo.classList.remove('is-valid');
    
    // Remover feedback anterior
    const feedbackAnterior = campo.parentNode.querySelector('.invalid-feedback');
    if (feedbackAnterior) feedbackAnterior.remove();
    
    // Agregar nuevo feedback
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = mensaje;
    campo.parentNode.appendChild(feedback);
}

// Marcar campo como válido
function marcarValido(campo) {
    campo.classList.add('is-valid');
    campo.classList.remove('is-invalid');
    
    // Remover feedback de error si existe
    const feedbackAnterior = campo.parentNode.querySelector('.invalid-feedback');
    if (feedbackAnterior) feedbackAnterior.remove();
}

// Inicializar animaciones
function inicializarAnimaciones() {
    // Agregar clase de animación a elementos recién cargados
    const elementos = document.querySelectorAll('.card, .table-responsive');
    elementos.forEach((elemento, index) => {
        elemento.classList.add('fade-in');
        elemento.style.animationDelay = `${index * 0.1}s`;
    });
}

// Confirmación mejorada para eliminación
document.addEventListener('click', function(e) {
    if (e.target.closest('a.btn-danger') || 
        (e.target.tagName === 'A' && e.target.classList.contains('btn-danger'))) {
        
        e.preventDefault();
        const url = e.target.href || e.target.closest('a').href;
        
        // Crear modal de confirmación personalizado
        const modalHtml = `
            <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar eliminación</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al documento si no existe
        if (!document.getElementById('confirmDeleteModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
        
        // Configurar acción de confirmación
        document.getElementById('confirmDeleteBtn').onclick = function() {
            window.location.href = url;
        };
    }
});