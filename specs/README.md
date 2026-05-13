# Mater-Natura — Especificaciones (Spec-Driven Design)

Este directorio contiene todas las especificaciones del proyecto,
organizadas en 3 niveles:

```
specs/
├── features/       ← Behat / Gherkin — escenarios de usuario
├── modules/        ← Markdown — specs detalladas por módulo
└── contracts/      ← PHP Interfaces — contratos compilables
```

## Flujo de trabajo

1. Feature Spec (Gherkin) → qué hace el usuario
2. Module Spec (markdown) → cómo se comporta el sistema
3. Contract Interface (PHP) → qué promete cada clase
4. PHPSpec → comportamiento esperado
5. Implementación → código que cumple el spec
