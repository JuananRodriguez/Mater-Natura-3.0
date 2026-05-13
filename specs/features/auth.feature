Feature: Autenticación
  Como administrador del blog poético
  Quiero iniciar y cerrar sesión de forma segura
  Para poder gestionar el contenido del sitio

  ## Antecedentes
  Given existe un usuario administrador "juanan" con:
    | email    | juanan@maternatura.com |
    | password | Argon2id hash de "P03s1sS3gur4!" |
    | role     | admin |
  And existe un usuario editor "poeta" con:
    | email    | poeta@maternatura.com |
    | password | Argon2id hash de "V3rs0sL1br3s" |
    | role     | editor |

  ---
  # Escenarios: Inicio de sesión
  ---

  Scenario: Inicio de sesión exitoso como administrador
    When accedo a /login
    Then veo un formulario con campos "Usuario/Email" y "Contraseña"
    And veo un campo oculto con un token CSRF
    When introduzco "juanan" y "P03s1sS3gur4!"
    And envío el formulario
    Then el sistema valida el token CSRF
    And verifica la contraseña contra Argon2id
    And el sistema inicia sesión
    And me redirige a /admin
    And veo el dashboard con mi nombre "juanan"

  Scenario: Inicio de sesión con email en lugar de username
    When introduzco "juanan@maternatura.com" y "P03s1sS3gur4!"
    And envío el formulario
    Then el sistema me autentica correctamente
    And me redirige a /admin

  Scenario: Inicio de sesión con contraseña incorrecta
    When introduzco "juanan" y "contraseña_incorrecta"
    And envío el formulario
    Then el sistema NO inicia sesión
    And veo el mensaje "Credenciales inválidas"
    And el sistema registra un intento fallido para la IP 127.0.0.1
    And el intento fallido se asocia al username "juanan"

  Scenario: Bloqueo por superar el límite de intentos
    Given he realizado 5 intentos fallidos desde la IP 192.168.1.1
    When intento iniciar sesión de nuevo con "juanan" y cualquier contraseña
    Then veo el mensaje "Demasiados intentos. Inténtalo de nuevo en 15 minutos."
    And el sistema NO verifica la contraseña (evita ataques de fuerza bruta)

  Scenario: Bloqueo expira tras 15 minutos
    Given he sido bloqueado tras 5 intentos fallidos desde IP 10.0.0.1
    And han pasado 16 minutos desde el primer intento
    When intento iniciar sesión con credenciales válidas
    Then el sistema permite el inicio de sesión
    And los registros de intentos fallidos para esa IP se limpian

  Scenario: El editor no puede acceder a funciones de admin
    Given soy el usuario "poeta" con rol editor
    And he iniciado sesión correctamente
    When accedo a /admin
    Then veo el dashboard
    But NO veo la sección de gestión de usuarios
    And NO veo la sección de gestión de plugins

  ---
  # Escenarios: Cierre de sesión
  ---

  Scenario: Cierre de sesión
    Given he iniciado sesión como "juanan"
    When accedo a /logout
    Then la sesión se destruye
    And la cookie de sesión se elimina
    And soy redirigido a /
    And ya no veo el enlace "Admin" en el menú

  Scenario: Cierre de sesión desde pestaña duplicada
    Given he iniciado sesión en dos pestañas
    When cierro sesión en la primera pestaña
    Then al recargar la segunda pestaña, ya no estoy autenticado
    And veo la pantalla de inicio público

  ---
  # Escenarios: Seguridad de sesión
  ---

  Scenario: La cookie de sesión es segura
    When recibo la cookie de sesión
    Then la cookie tiene el flag HttpOnly
    And la cookie tiene el flag SameSite=Strict
    And la cookie tiene el flag Secure (en producción)
    And el nombre de la cookie no es el predeterminado "PHPSESSID"

  Scenario: Regeneración de ID de sesión tras login
    Given tengo un ID de sesión antes de autenticarme
    When inicio sesión correctamente
    Then el ID de sesión es diferente al anterior
    And el ID anterior ya no es válido

  Scenario: Sesión expira por inactividad
    Given he iniciado sesión como "juanan"
    When pasan 35 minutos sin actividad
    Then al hacer una petición a /admin
    And veo la pantalla de login
    And veo el mensaje "Tu sesión ha expirado por inactividad"

  Scenario: Protección contra sesión fija
    Given un atacante establece un ID de sesión conocido "ID_MALICIOSO"
    When inicio sesión exitosamente
    Then el sistema NO utiliza "ID_MALICIOSO"
    And genera un nuevo ID de sesión

  Scenario: CSRF token inválido
    Given he iniciado sesión como "juanan"
    When envío un formulario POST sin token CSRF
    Then el sistema rechaza la petición
    And veo el mensaje "Token de seguridad inválido"
    And la acción NO se ejecuta

  ---
  # Escenarios: Doble factor (TOTP) [opcional — post-MVP]
  ---

  Scenario: Usuario con 2FA habilita la verificación en dos pasos
    Given soy "juanan" con sesión iniciada
    When accedo a /admin/seguridad
    And habilito la autenticación de doble factor
    Then el sistema me muestra un código QR para escanear
    And me pide que introduzca un código de verificación
    When introduzco un código TOTP válido
    Then el 2FA queda activado

  Scenario: Inicio de sesión con 2FA activo
    Given el usuario "juanan" tiene 2FA activado
    When introduzco credenciales correctas en /login
    Then el sistema NO me redirige a /admin
    And me muestra un formulario para introducir el código 2FA
    When introduzco el código TOTP correcto
    Then el sistema me redirige a /admin
