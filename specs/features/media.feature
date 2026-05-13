Feature: Gestión de imágenes (Media)
  Como autor del blog poético
  Quiero subir una imagen para cada post
  Para ilustrar mis poemas

  ---
  # Escenarios: Subida y almacenamiento
  ---

  Scenario: Subir imagen al crear un post
    When adjunto un archivo JPEG válido de 1 MB
    Then el sistema valida el tipo MIME real con finfo
    And renombra el archivo a /uploads/images/2025/05/{slug}.jpg
    And redimensiona la imagen a un máximo de 1200px de ancho
    And convierte el perfil de color a sRGB
    And guarda la imagen fuera del document root
    And registra la ruta relativa en el campo image_url del post

  Scenario: Formato de imagen aceptado
    Given los formatos aceptados son JPEG, PNG y WebP
    When subo un archivo JPEG → se acepta
    When subo un archivo PNG → se acepta
    When subo un archivo WebP → se acepta
    When subo un archivo GIF → se rechaza
    When subo un archivo SVG → se rechaza
    When subo un archivo BMP → se rechaza

  Scenario: Validación de tipo MIME real
    Given un atacante sube un archivo "imagen.jpg" que en realidad es un script PHP
    When el sistema analiza el tipo MIME real con finfo
    Then detecta que es application/x-php
    And rechaza el archivo
    And registra el intento en los logs de seguridad

  Scenario: Límite de tamaño de imagen
    Given el tamaño máximo permitido es 5 MB
    When subo una imagen de 3 MB → se acepta
    When subo una imagen de 6 MB → se rechaza

  Scenario: Prevención de path traversal en el nombre del archivo
    Given un atacante sube un archivo con nombre "../../etc/passwd"
    When el sistema procesa el archivo
    Then el nombre se sanitiza eliminando ".." y "/"
    And el archivo se guarda con un nombre generado por el sistema, no el original

  ---
  # Escenarios: Servir imágenes
  ---

  Scenario: Las imágenes se sirven a través de PHP (no directamente)
    Given existe la imagen luna-de-abril.jpg en uploads/images/2025/05/
    When accedo a /media/images/2025/05/luna-de-abril.jpg
    Then PHP localiza el archivo en uploads/
    And establece el Content-Type correcto
    And establece la cabecera Cache-Control para caché del navegador
    And sirve la imagen con el código HTTP 200

  Scenario: Imagen no encontrada devuelve 404
    When accedo a /media/images/2025/05/no-existe.jpg
    Then recibo un HTTP 404
    And sirve una imagen por defecto "no-disponible.jpg"

  Scenario: No se puede acceder a uploads/ directamente
    When accedo a /uploads/images/2025/05/luna-de-abril.jpg
    Then recibo un error HTTP 403 Forbidden
    Or soy redirigido a /
