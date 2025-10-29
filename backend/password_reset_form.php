<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Restablecer contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        background: url('/Agora/Agora/img/itsp.jpeg') no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }
    
         /* Capa de difuminado del fondo */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4); /* oscurece un poco el fondo */
            backdrop-filter: blur(8px); /* efecto difuminado */
            z-index: 0;
        }
    
    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        width: 100%;
        max-width: 420px;
        background: rgba(255,255,255,0.95);
    }
    .password-requirements {
        font-size: 0.8em;
        margin-top: 8px;
        color: #555;
    }
    .password-requirements li {
        list-style: none;
        margin-bottom: 3px;
    }
    .valid {
        color: #28a745;
    }
    .invalid {
        color: #dc3545;
    }
    .progress {
        height: 8px;
        border-radius: 10px;
        background-color: #e9ecef;
    }
    .progress-bar {
        transition: width 0.3s ease;
        border-radius: 10px;
    }
</style>
</head>

<body>
<div class="card p-4">
    <h3 class="text-center mb-4 fw-bold">🔒 Nueva contraseña</h3>

    <form action="password_update.php" method="POST" id="resetForm">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

        <div class="mb-3">
            <label for="password" class="form-label">Nueva contraseña</label>
            <input 
                type="password" 
                name="password" 
                id="password" 
                class="form-control" 
                minlength="8" maxlength="24" 
                required 
                placeholder="••••••••••">
        </div>

        <div class="progress mb-2">
            <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%;"></div>
        </div>
        <small class="text-muted d-block text-center mb-2">Nivel de seguridad</small>

        <ul class="password-requirements ps-2">
            <li id="length" class="invalid">8 a 24 caracteres</li>
            <li id="uppercase" class="invalid">Una letra mayúscula</li>
            <li id="lowercase" class="invalid">Una letra minúscula</li>
            <li id="number" class="invalid">Un número</li>
            <li id="special" class="invalid">Un carácter especial (!@#$%^&*?)</li>
        </ul>

        <button type="submit" class="btn btn-success w-100 mt-3" disabled id="submitBtn">
            Actualizar contraseña
        </button>
    </form>
</div>

<script>
const passwordInput = document.getElementById('password');
const submitBtn = document.getElementById('submitBtn');
const strengthBar = document.getElementById('passwordStrength');

const checks = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    const validations = {
        length: value.length >= 8 && value.length <= 24,
        uppercase: /[A-Z]/.test(value),
        lowercase: /[a-z]/.test(value),
        number: /\d/.test(value),
        special: /[!@#$%^&*?]/.test(value)
    };

    let validCount = 0;
    for (const [key, valid] of Object.entries(validations)) {
        checks[key].classList.toggle('valid', valid);
        checks[key].classList.toggle('invalid', !valid);
        if (valid) validCount++;
    }

    // Calcular fuerza de contraseña (20% por cada requisito cumplido)
    const strength = validCount * 20;
    strengthBar.style.width = strength + "%";

    // Cambiar color según fuerza
    if (strength <= 40) {
        strengthBar.style.backgroundColor = "#dc3545"; // rojo
    } else if (strength <= 80) {
        strengthBar.style.backgroundColor = "#ffc107"; // amarillo
    } else {
        strengthBar.style.backgroundColor = "#28a745"; // verde
    }

    // Habilitar botón solo si todos los requisitos están completos
    submitBtn.disabled = validCount < 5;
});
</script>
</body>
</html>
