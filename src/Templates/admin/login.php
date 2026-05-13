<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceder &mdash; <?= $escape(MATER_SITE_NAME) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Titillium+Web:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (estático) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        body {
            background: #f9f9f9;
            font-family: -apple-system, "system-ui", "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 8%;
        }
        #login {
            width: 320px;
            padding: 0 8px;
        }
        /* Logo */
        .login-logo {
            text-align: center;
            display: block;
            margin: 0 auto 20px;
        }
        .login-logo img {
            max-width: 84px;
            height: auto;
        }
        /* Formulario */
        .login-form {
            background: #fff;
            border: 1px solid #e6e6e6;
            padding: 26px 24px;
            margin-top: 0;
        }
        .login-form label {
            display: block;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 3px;
            color: #3c434a;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 3px 5px;
            font-size: 24px;
            line-height: 1.333;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            background: #fff;
            color: #3c434a;
            margin-bottom: 16px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .login-form input[type="text"]:focus,
        .login-form input[type="password"]:focus {
            border-color: #2d2d2d;
            box-shadow: 0 0 0 1px #2d2d2d;
        }
        .login-form .submit-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .login-form input[type="submit"] {
            background: #000;
            border: 1px solid #000;
            color: #fff;
            padding: 0 12px;
            font-size: 13px;
            line-height: 2.15384615;
            min-height: 30px;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.15s;
            display: inline-block;
        }
        .login-form input[type="submit"]:hover {
            background: #1a1a1a;
            border-color: #1a1a1a;
        }
        .login-form .forgetmenot {
            float: left;
            line-height: 1.5;
        }
        .login-form .forgetmenot label {
            font-size: 13px;
        }
        .login-form .forgetmenot input[type="checkbox"] {
            margin: 0 4px 0 0;
            vertical-align: text-bottom;
        }
        .login-error {
            background: #fcf0f1;
            border-left: 4px solid #d63638;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .login-warning {
            background: #fcf9e8;
            border-left: 4px solid #dba617;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .login-links {
            margin-top: 16px;
            text-align: center;
        }
        .login-links a {
            color: #50575e;
            font-size: 13px;
            text-decoration: none;
        }
        .login-links a:hover {
            color: #2d2d2d;
        }
        .login-links .sep {
            color: #ccc;
            padding: 0 4px;
        }
        .login #nav {
            margin: 24px 0 0;
            font-size: 13px;
        }
        .login #nav a {
            color: #50575e;
            text-decoration: none;
        }
        .login #nav a:hover {
            color: #2d2d2d;
        }
        .login #backtoblog {
            margin: 16px 0 0;
            text-align: center;
            font-size: 13px;
        }
        .login #backtoblog a {
            color: #50575e;
            text-decoration: none;
        }
        .login #backtoblog a:hover {
            color: #2d2d2d;
        }
        .privacy-policy {
            text-align: center;
            margin-top: 24px;
        }
        .privacy-policy a {
            color: #50575e;
            font-size: 13px;
            text-decoration: none;
        }
        .privacy-policy a:hover {
            color: #2d2d2d;
        }
    </style>
</head>
<body class="login">
    <div id="login">
        <!-- Logo Mater-Natura (en lugar del de WordPress) -->
        <a href="/" class="login-logo">
            <img src="/assets/img/Mater-Natura.gif" alt="Mater-Natura" title="Mater-Natura">
        </a>

        <h1 class="sr-only">Acceder</h1>

        <?php if ($error): ?>
            <div class="login-error"><?= $escape($error) ?></div>
        <?php endif; ?>
        <?php if ($expired): ?>
            <div class="login-warning">Tu sesión ha expirado por inactividad.</div>
        <?php endif; ?>

        <form method="POST" action="/login" class="login-form" name="loginform" id="loginform">
            <?= $csrfField ?>

            <p>
                <label for="username">Nombre de usuario o correo electrónico</label>
                <input type="text" name="username" id="username" class="input" value="" size="20" autocapitalize="off" autocomplete="username" required autofocus>
            </p>
            <p>
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="input" value="" size="20" autocomplete="current-password" required>
            </p>

            <p class="submit-wrap">
                <span class="forgetmenot">
                    <label for="rememberme">
                        <input name="rememberme" type="checkbox" id="rememberme" value="forever" checked>
                        Recuérdame
                    </label>
                </span>
                <input type="submit" name="wp-submit" id="wp-submit" value="Acceder">
            </p>
        </form>

        <p id="nav">
            <a href="#">¿Has olvidado tu contraseña?</a>
        </p>

        <p id="backtoblog">
            <a href="/"><?= svg_icon('chevron-left') ?> Ir a Mater Natura</a>
        </p>
    </div>
</body>
</html>
