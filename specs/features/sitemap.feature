Feature: Sitemap XML
  Como administrador del blog
  Quiero que el sitemap.xml se genere automáticamente
  Para que los buscadores indexen correctamente el contenido

  ---
  # Escenarios: Generación
  ---

  Scenario: Generación inicial del sitemap
    Given existen 3 posts publicados y 2 páginas publicadas
    When accedo a /sitemap.xml
    Then veo un documento XML válido con:
    | <urlset> con xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" |
    And contiene una entrada <url> para cada post publicado
    And contiene una entrada <url> para cada página publicada
    And contiene una entrada <url> para la home /
    And contiene una entrada <url> para el listado /post
    And cada <url> incluye <loc>, <lastmod> y <priority>

  Scenario: Prioridades del sitemap
    Given los niveles de prioridad definidos son:
      | home    | 1.0 |
      | pages   | 0.8 |
      | posts   | 0.6 |
      | /post   | 0.5 |
    When se genera el sitemap
    Then cada entrada tiene la prioridad correcta

  Scenario: El sitemap excluye borradores
    Given existen 2 posts publicados y 1 en borrador
    When accedo a /sitemap.xml
    Then el post en borrador NO aparece en el XML

  Scenario: El sitemap excluye páginas en borrador
    Given existe 1 página en borrador
    When accedo a /sitemap.xml
    Then la página en borrador NO aparece

  ---
  # Escenarios: Actualización automática
  ---

  Scenario: Se regenera al crear un post
    Given el sitemap existe con 3 posts
    When creo un nuevo post y lo publico
    Then se añade una entrada a la cola de regeneración del sitemap
    And el sitemap.xml se regenera incluyendo el nuevo post
    And el campo <lastmod> del sitemap se actualiza a la fecha actual

  Scenario: Se regenera al actualizar un post
    Given el sitemap existe con el post "luna-de-abril" con lastmod 2025-04-01
    When actualizo el post "luna-de-abril"
    Then el sitemap se regenera
    And el <lastmod> de "luna-de-abril" se actualiza a la fecha actual

  Scenario: Se regenera al eliminar un post
    Given el sitemap contiene "luna-de-abril"
    When elimino el post "luna-de-abril"
    Then el sitemap se regenera
    And "luna-de-abril" ya no aparece en el XML

  Scenario: Regeneración bajo demanda
    Given he iniciado sesión como "juanan"
    When accedo a /admin/ajustes
    And hago clic en "Regenerar sitemap"
    Then el sitemap se regenera inmediatamente
    And veo el mensaje "Sitemap regenerado correctamente"

  Scenario: La regeneración NO ocurre si no hay cambios
    Given no se ha creado, modificado ni eliminado contenido
    When accedo a /sitemap.xml
    Then el sitemap es el mismo que la última vez
