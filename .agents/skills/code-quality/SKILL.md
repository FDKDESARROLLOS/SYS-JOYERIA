---
name: code-quality
description: Mejora legibilidad, organización, formato y mantenibilidad del código del proyecto Laravel sin cambiar su comportamiento.
---

# Code Quality

## Objetivo

Mantener el código limpio, entendible, consistente y fácil de mantener.

## Reglas generales

- No cambiar funcionalidad si la tarea es solamente refactorizar o formatear.
- Evitar código duplicado.
- Evitar archivos innecesariamente grandes.
- Separar responsabilidades.
- Usar nombres claros y descriptivos.
- Evitar variables genéricas como `data`, `item` o `temp` cuando exista un nombre más específico.
- Mantener funciones pequeñas cuando sea razonable.
- Evitar lógica compleja directamente en vistas Blade.
- Mantener indentación y espaciado consistentes.
- Eliminar código muerto.
- Eliminar comentarios innecesarios.
- No agregar comentarios que solamente repitan lo que hace el código.
- Preferir claridad y mantenibilidad antes que reducir la cantidad de líneas.

## Formato visual del código

- Priorizar formato vertical sobre código compacto.
- No escribir bloques complejos en una sola línea.
- Limitar líneas largas aproximadamente a 100-120 caracteres.
- Si una línea se vuelve difícil de leer, dividirla aunque todavía no haya alcanzado ese límite.
- Separar atributos Blade y HTML largos en varias líneas.
- Separar arrays PHP con una clave por línea.
- Separar objetos, configuraciones y estructuras complejas en varias líneas.
- Separar parámetros largos de funciones o componentes en varias líneas.
- Dejar una línea en blanco entre bloques lógicos.
- Mantener indentación consistente.
- No sacrificar legibilidad para reducir líneas.
- Evitar grandes bloques visualmente amontonados.

### Ejemplo de componente Blade

Preferir:

```blade
<x-admin.stat-card
    title="Ventas del mes"
    value="$48,290"
    description="vs. mes anterior"
    icon="sales"
/>
```
