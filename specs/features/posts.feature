Feature: Posts
  Como autor del blog poético
  Quiero crear, editar, publicar y listar posts
  Para compartir mis poemas con una imagen y elegir entre tema oscuro o claro

  ---
  # Escenarios: Visualización pública
  ---

  Scenario: Ver un post individual por su slug
    Given existe un post publicado con:
      | title     | Luna de abril |
      | slug      | luna-de-abril |
      | template  | dark |
      | image     | luna-abril.jpg |
    When accedo a /luna-de-abril
    Then veo el título "Luna de abril"
    And veo la imagen luna-abril.jpg
    And veo el texto poético completo
    And la página usa la plantilla dark
    And veo metadatos: autor y fecha de publicación
    And el SEO incluye:
      | meta title       | Luna de abril |
      | meta description | (primeros 160 caracteres del poema) |
      | og:image         | /media/images/2025/05/luna-abril.jpg |
      | og:type          | article |
    And la URL en el navegador es /luna-de-abril

  Scenario: Post con template light se muestra en tema claro
    Given existe un post publicado con template "light"
    When accedo a su URL
    Then veo el contenido con la plantilla light
    And el cuerpo tiene la clase CSS "theme-light"
    And los colores, tipografía y fondo corresponden al tema claro

  Scenario: Post en estado borrador NO es accesible públicamente
    Given existe un post en estado "draft" con slug "poema-secreto"
    When accedo a /poema-secreto
    Then veo un error 404
    And NO veo el contenido del poema

  Scenario: Acceso a slug que no existe
    When accedo a /un-poema-que-no-existe
    Then veo un error 404
    And veo una página personalizada con el mensaje "Este poema no ha sido escrito aún"

  Scenario: Listado de posts en /post
    Given existen 12 posts publicados
    When accedo a /post
    Then veo una lista paginada de posts
    And veo 10 posts por página
    And cada post en el listado muestra: título, imagen miniatura, primeros versos, fecha
    And veo enlaces de paginación "Siguiente" y "Anterior"
    When accedo a /post?page=2
    Then veo los 2 posts restantes

  Scenario: Slug de post no colisiona con rutas del sistema
    Given existe una ruta del sistema /admin, /login, /post, /sitemap.xml
    When un usuario intenta crear un post con slug "admin"
    Then el sistema rechaza el slug "admin"
    And muestra el mensaje "Este slug está reservado por el sistema"
    And lo mismo ocurre para: login, logout, post, sitemap.xml, media, page

  ---
  # Escenarios: Administración de posts
  ---

  Scenario: Crear un post nuevo desde el panel
    Given he iniciado sesión como "juanan"
    When accedo a /admin/posts/editar
    Then veo un formulario con los campos:
      | Título        | text     | requerido |
      | Slug          | text     | auto-generado desde el título |
      | Descripción   | textarea | requerido |
      | Imagen        | file     | requerido |
      | Template      | select   | dark / light, default: dark |
      | Estado        | select   | publicado / borrador |
    When relleno el formulario con un post válido
    And adjunto una imagen JPEG
    And envío el formulario
    Then el sistema valida que todos los campos requeridos están presentes
    And valida que la imagen es un formato permitido (JPEG, PNG, WebP)
    And redimensiona la imagen a un tamaño máximo (ancho 1200px)
    And guarda la imagen en /uploads/images/2025/05/luna-de-abril.jpg
    And registra el post en la base de datos
    And muestra el mensaje "Post creado correctamente"
    And veo el listado de posts con el nuevo post incluido

  Scenario: Validación de imagen — formato no permitido
    When adjunto un archivo .gif como imagen del post
    Then el sistema rechaza el archivo
    And muestra el mensaje "Formato de imagen no permitido. Usa JPEG, PNG o WebP"
    And el post NO se guarda

  Scenario: Validación de imagen — archivo demasiado grande
    When adjunto una imagen de 12 MB
    Then el sistema rechaza el archivo
    And muestra "La imagen supera el tamaño máximo de 5 MB"

  Scenario: Editar un post existente
    Given existe un post "Luna de abril" con slug "luna-de-abril"
    When accedo a /admin/posts/editar?id=1
    Then veo el formulario precargado con los datos del post
    When cambio el título a "Luna de mayo"
    And cambio el template a "light"
    And envío el formulario
    Then el sistema actualiza el post
    And la URL /luna-de-abril sigue funcionando (el slug no ha cambiado)
    But el título visible es ahora "Luna de mayo"
    And el post se muestra ahora con template light
    And se añade una entrada a la cola de regeneración del sitemap

  Scenario: Slug auto-generado desde el título
    When escribo el título "El jardín de los versos"
    Then el campo slug se rellena automáticamente con "el-jardin-de-los-versos"
    When cambio manualmente el slug a "mi-jardin"
    Then el slug se mantiene como "mi-jardin" (no se sobreescribe)

  Scenario: Slug duplicado al crear post
    Given existe un post con slug "atardecer"
    When intento crear un post con slug "atardecer"
    Then el sistema rechaza el slug duplicado
    And sugiere "atardecer-1"

  Scenario: Eliminar un post
    Given existe un post "Luna de abril"
    When accedo a /admin/posts
    And hago clic en "Eliminar" para "Luna de abril"
    Then veo un modal de confirmación
    When confirmo la eliminación
    Then el post se elimina de la base de datos
    And la imagen asociada NO se elimina del servidor (seguridad)
    And veo el mensaje "Post eliminado correctamente"
    And el post ya no aparece en el listado público
    And se añade una entrada a la cola de regeneración del sitemap

  Scenario: La home NO puede ser un post (la home es una página fija)
    Given el sistema reserva la ruta "/" para la página de inicio
    When intento crear un post con slug "index" o "home"
    Then el sistema lo rechaza como slug reservado
