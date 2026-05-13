Feature: Sistema de Plugins
  Como administrador del blog
  Quiero gestionar plugins desde el panel de admin
  Para extender la funcionalidad sin modificar el núcleo

  ---
  # Escenarios: Descubrimiento y listado
  ---

  Scenario: El admin muestra los plugins instalados
    Given existe un plugin "Analytics" instalado en src/Plugins/Analytics/
    And existe un plugin "SocialShare" instalado en src/Plugins/SocialShare/
    When accedo a /admin/plugins
    Then veo una tabla con todos los plugins detectados
    And cada fila muestra: nombre, versión, descripción, estado (activado/desactivado)
    And veo acciones: Activar/Desactivar, Configurar

  Scenario: Solo se detectan plugins que implementan PluginInterface
    Given existe un directorio src/Plugins/Malware/ sin PluginInterface
    When el sistema escanea los plugins
    Then Malware NO aparece en el listado del admin
    And el PluginManager solo carga clases que implementan PluginInterface

  Scenario: Plugin con metadatos incompletos
    Given un plugin devuelve un array vacío en getMeta()
    When el sistema lo procesa
    Then el plugin aparece como "Nombre desconocido" y "Versión 0.0.0"
    And se muestra un warning visual en el panel

  ---
  # Escenarios: Ciclo de vida
  ---

  Scenario: Activar un plugin
    Given el plugin "Analytics" está instalado pero desactivado
    When hago clic en "Activar" para Analytics
    Then el sistema ejecuta Plugin::onActivate()
    And actualiza la base de datos: plugins.enabled = TRUE
    And registra los hooks que expone el plugin en plugin_hooks
    And veo el mensaje "Plugin Analytics activado correctamente"

  Scenario: Desactivar un plugin
    Given el plugin "SocialShare" está activado
    When hago clic en "Desactivar"
    Then el sistema ejecuta Plugin::onDeactivate()
    And actualiza plugins.enabled = FALSE
    And elimina los hooks registrados para ese plugin
    And veo "Plugin SocialShare desactivado"

  Scenario: Reactivar un plugin
    Given el plugin "Analytics" fue activado y luego desactivado
    When lo activo de nuevo
    Then vuelve a registrar sus hooks

  ---
  # Escenarios: Hook system
  ---

  Scenario: El hook se ejecuta en el punto correcto
    Given el plugin "Analytics" está activado
    And registra un hook en "entry.render.after"
    When se renderiza un post público
    Then después del contenido del post, se ejecuta Analytics::renderAfter()
    And el HTML del plugin se inyecta a continuación del artículo

  Scenario: Múltiples plugins en el mismo hook
    Given "Analytics" y "SocialShare" están ambos activados
    And ambos registran el hook "entry.render.after"
    And Analytics tiene prioridad 10, SocialShare prioridad 20
    When se renderiza un post
    Then Analytics se ejecuta primero (prioridad 10)
    And SocialShare se ejecuta después (prioridad 20)

  Scenario: Los hooks no se ejecutan si el plugin está desactivado
    Given "SocialShare" está desactivado
    When se renderiza un post
    Then SocialShare NO se ejecuta aunque tenga hooks registrados
    And el renderizado continúa normalmente sin errores

  ---
  # Escenarios: Seguridad
  ---

  Scenario: Un plugin no puede modificar el núcleo
    Given un plugin malicioso intenta sobrescribir Database.php
    When el plugin se carga
    Then el sistema impide la modificación de archivos del núcleo
    And el plugin solo puede actuar a través de hooks

  Scenario: Error en un plugin no rompe el sistema
    Given el plugin "BrokenPlugin" lanza una excepción en entry.render.after
    When se renderiza un post
    Then el error se captura (try/catch)
    And el post se muestra correctamente sin el contenido del plugin
    And el error se registra en los logs
    And el resto de plugins en ese hook se ejecutan con normalidad
