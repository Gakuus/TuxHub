class RecursosForm {
    constructor() {
        this.form = document.getElementById('formRecurso');
        this.tipoSelect = document.getElementById('tipo');
        this.salonField = document.getElementById('salonField');
        this.salonSelect = document.getElementById('salon_id');
        this.init();
    }

    init() {
        this.toggleSalonField();
        this.bindEvents();
    }

    bindEvents() {
        if (this.tipoSelect) {
            this.tipoSelect.addEventListener('change', () => this.toggleSalonField());
        }
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }
    }

    toggleSalonField() {
        if (!this.tipoSelect || !this.salonField || !this.salonSelect) return;
        const tipo = this.tipoSelect.value;
        if (tipo === 'Llave' || tipo === 'Control') {
            this.salonField.style.display = 'block';
            this.salonSelect.required = true;
            setTimeout(() => { this.salonField.style.opacity = '1'; }, 10);
        } else {
            this.salonField.style.display = 'none';
            this.salonSelect.required = false;
            this.salonSelect.value = '';
        }
    }

    validateForm(e) {
        const nombre = document.getElementById('nombre')?.value.trim();
        const tipo = this.tipoSelect?.value;
        const salon_id = this.salonSelect?.value;

        if (!nombre) {
            e.preventDefault();
            ToastSystem.warning('Validación', 'Por favor ingresa un nombre para el recurso.');
            document.getElementById('nombre')?.focus();
            return false;
        }

        if (!tipo) {
            e.preventDefault();
            ToastSystem.warning('Validación', 'Por favor selecciona un tipo de recurso.');
            this.tipoSelect?.focus();
            return false;
        }

        if ((tipo === 'Llave' || tipo === 'Control') && !salon_id) {
            e.preventDefault();
            ToastSystem.warning('Validación', `Por favor selecciona un salón para el recurso de tipo ${tipo}.`);
            this.salonSelect?.focus();
            return false;
        }

        return true;
    }

    setEditValues(data) {
        console.log('Datos de edición cargados:', data);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.recursosForm = new RecursosForm();
});

function toggleSalonField() {
    if (window.recursosForm) window.recursosForm.toggleSalonField();
}
