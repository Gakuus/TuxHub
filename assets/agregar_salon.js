function validarNumero(input) {
    // Remover cualquier caracter que no sea número
    input.value = input.value.replace(/[^0-9]/g, '');
    
    // Limitar el valor máximo a 50
    if (input.value > 50) {
        input.value = 50;
    }
    
    // Asegurar que no esté vacío y sea al menos 1
    if (input.value === '' || input.value < 1) {
        input.value = 1;
    }
}

function validarFormulario() {
    const capacidad = document.querySelector('input[name="capacidad"]');
    
    // Validación final antes de enviar
    if (capacidad.value === '' || capacidad.value < 1) {
        alert('La capacidad debe ser al menos 1 persona');
        capacidad.focus();
        return false;
    }
    
    if (capacidad.value > 50) {
        alert('La capacidad no puede ser mayor a 50 personas');
        capacidad.focus();
        return false;
    }
    
    return true;
}

// Prevenir pegado de texto no numérico
document.addEventListener('DOMContentLoaded', function() {
    const capacidadInput = document.querySelector('input[name="capacidad"]');
    
    if (capacidadInput) {
        capacidadInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pastedText.replace(/[^0-9]/g, '');
            if (numbersOnly > 50) {
                this.value = 50;
            } else {
                this.value = numbersOnly;
            }
        });
    }
});