<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fafccc;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .background-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            z-index: 1;
        }
        .background-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .login-container {
            background-color: rgba(103, 146, 114, 0.8);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }
        .login-container h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .input-group {
            width: 100%;
            margin-bottom: 20px;
            position: relative;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #264937;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #264937;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #193229;
        }
        .error-message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="background-container">
        <img src="./Imagenes/JALIT8.jpg" class="background-image" alt="Imagen de fondo">
    </div>
    <div class="login-container">
        <h1>Iniciar sesión</h1>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <form method="post" action="Process_Login.php" autocomplete="off" id="loginForm">
            <div class="input-group">
                <input type="text" name="username" placeholder="Nombre de usuario" required autocomplete="off">
            </div>
            <div class="input-group password-container">
                <input type="password" name="password" id="passwordField" placeholder="Contraseña" required autocomplete="off">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            <button type="submit">Ingresar</button>
        </form>
        <h4>Jardín Literario</h4>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('passwordField');
            const icon = this;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        const swalCustom = Swal.mixin({
            background: '#fafccc',
            confirmButtonColor: '#264937',
            color: '#264937',
            iconColor: '#264937'
        });

        <?php if (!empty($error)): ?>
            swalCustom.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= addslashes($error) ?>'
            });
        <?php endif; ?>

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.elements['username'].value.trim();
            const password = this.elements['password'].value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                swalCustom.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor complete todos los campos'
                });
            }
        });
    </script>
</body>
</html>