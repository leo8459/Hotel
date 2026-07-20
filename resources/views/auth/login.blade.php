<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }

        .login-container {
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: flex-start;
            padding: 0 10%;
            position: relative;
        }

        .card-view {
            background: #0056B3;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            color: white;
            width: 100%;
            max-width: 450px;
            height: auto;
            min-height: 450px; /* CardView más alto */
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-left: -1%;
        }

        .card-view h2 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            text-align: center;
        }

        .card-view input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            color: #000000; /* Azul EMS */
            background-color: #f9f9f9;
        }

        .card-view input:focus {
            outline: none;
            box-shadow: 0 0 8px rgba(243, 156, 18, 0.8); /* Naranja brillante al enfocar */
        }

        .password-container {
        position: relative;
        width: 100%;
    }

    .password-container input {
        width: 100%;
        padding-right: 10px; /* Espacio reservado para el ojo */
    }

    .password-container .toggle-password {
        position: absolute;
        top: 50%;
        right: -10px; /* Ajusta la posición horizontal */
        transform: translateY(-50%);
        font-size: 20px; /* Tamaño del icono del ojo */
        color: #0056B3; /* Azul EMS */
        cursor: pointer;
    }

        .card-view button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: #F39C12; /* Botón naranja EMS */
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .card-view button:hover {
            background-color: #D8870F; /* Naranja más oscuro al hover */
            transform: translateY(-3px);
        }

        /* .g-recaptcha {
            margin: 20px 0;
        } */

        /* Responsividad */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }

            .card-view {
                max-width: 90%;
                padding: 20px;
                margin-left: 0;
            }

            .card-view h2 {
                font-size: 22px;
            }

            .card-view input,
            .card-view button {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Formulario -->
        <div class="card-view">
            <h2>INICIAR SESIÓN</h2>
            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                <input type="email" name="email" placeholder="Email" required>
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                </div>
                <!-- <div class="g-recaptcha" data-sitekey="6Leg8LEqAAAAAIl35EcAbLmLidB3fDsrzgTQv-Fl"></div> -->
                <button type="submit" id="loginButton">INGRESAR</button>
            </form>
        </div>
    </div>

    <script>
          function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈'; // Cambia al icono de ocultar
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️'; // Cambia al icono de mostrar
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function () {
        const button = document.getElementById('loginButton');
        button.disabled = true;
        button.textContent = 'INGRESANDO...';
    });
        // const loginForm = document.getElementById('loginForm');
        // loginForm.addEventListener('submit', function(event) {
        //     const recaptchaResponse = document.querySelector('.g-recaptcha-response').value;
        //     if (!recaptchaResponse) {
        //         event.preventDefault();
        //         alert("Por favor completa el Captcha.");
        //     }
        // });
    </script>
</body>
</html>
