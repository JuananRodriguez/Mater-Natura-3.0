Feature: Pages
  Como administrador del blog
  Quiero crear páginas estáticas
  Para tener secciones fijas como "Sobre mí" o "Contacto"

  ---
  # Escenarios: Visualización pública
  ---

  Scenario: Ver una página por su slug
    Given existe una página publicada con:
      | title   | Sobre mí |
      | slug    | sobre-mi |
      | content | <p>Soy un poeta que escribe bajo la luna</p> |
    When accedo a /sobre-mi
    Then veo el título "Sobre mí"
    And veo el contenido HTML renderizado
    And la página usa la plantilla dark (por defecto)
    And el SEO incluye:
      | meta title       | Sobre mí |
      | og:type          | website |
    And la URL es /sobre-mi

  Scenario: Página en borrador no es accesible
    Given existe una página en draft con slug "secreto"
    When accedo a /secreto
    Then veo un error 404

  ---
  # Escenarios: Administración de páginas
  ---

  Scenario: Crear una página nueva
    Given he iniciado sesión como "juanan"
    When accedo a /admin/pages/editar
    Then veo un formulario con campos:
      | Título     | text     | requerido |
      | Slug       | text     | auto-generado |
      | Contenido  | textarea | requerido |
      | Template   | select   | dark / light |
      | Estado     | select   | publicado / borrador |
    When relleno el formulario
    And envío
    Then veo "Página creada correctamente"
    And la página es accesible en su slug

  Scenario: Editar una página existente
    Given existe la página "Sobre mí"
    When edito su contenido
    Then el contenido se actualiza

  Scenario: Eliminar una página
    Given existe la página "Contacto" (no es la home)
    When la elimino desde el admin
    Then la página se elimina
    And la ruta /contacto devuelve 404

  ---
  # Escenarios: Protección de la página de inicio
  ---

  Scenario: La página de inicio (/) es una página fija y protegida
    Given el sistema tiene una página de inicio configurada
    When accedo a /
    Then veo el contenido de la página de inicio
    And NO puedo eliminar la página de inicio desde el admin
    And el botón de eliminar no aparece para la página principal

  Scenario: Cambiar la página de inicio
    Given he iniciado sesión como "juanan"
    When accedo a /admin/ajustes
    And selecciono que la página "Sobre mí" sea la nueva home
    Then la ruta / muestra ahora el contenido de "Sobre mí"
    And la página anterior de inicio queda como página normal
