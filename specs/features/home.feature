Feature: Página de inicio (Home)
  Como visitante del blog
  Quiero ver una página de inicio fija con la identidad del blog
  Para saber qué es Mater-Natura y sentir su atmósfera poética

  Scenario: La home se muestra al acceder a la raíz
    When accedo a /
    Then veo la página de inicio configurada
    And veo el título del blog
    And veo el contenido de bienvenida
    And el SEO de la página incluye meta etiquetas propias de la home

  Scenario: La home usa la plantilla que el administrador elija
    Given la página de inicio está configurada con template "light"
    When accedo a /
    Then la home se muestra con la plantilla light

  Scenario: La home es editable desde el panel de admin
    Given he iniciado sesión como "juanan"
    When accedo a /admin/pages/editar
    And selecciono la página que es la home
    Then veo el editor de contenido
    When modifico el contenido de bienvenida
    And guardo
    Then la home muestra el nuevo contenido

  Scenario: La home NO se puede eliminar
    Given existe una página configurada como home
    When accedo al listado de páginas en /admin/pages
    Then la página de inicio no tiene botón "Eliminar"
    And si intento acceder a /admin/pages/eliminar?id={home_id}
    Then el sistema rechaza la acción
    And muestra "No puedes eliminar la página de inicio"
