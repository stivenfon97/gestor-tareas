---
paths:
  - 'resources/js/components/**'
---

# Components

## Prohibido cualquier color literal en componentes
Sin hex (`#10b981`), sin utilidades crudas de paleta (`bg-yellow-100`, `text-blue-600`, `border-red-500`). **Solo tokens semánticos** definidos en el tema.

Razón: un literal como `bg-yellow-100` no tiene par oscuro. En modo oscuro queda texto claro sobre fondo claro y el badge se vuelve ilegible. El token sí define ambos modos.

Cada componente debe verse correcto en modo claro **y** oscuro. Compruébalo antes de dar el trabajo por terminado.

Jerarquía visual: solo el **estado** lleva relleno de color; el **tipo** va como icono monocromo y la **prioridad** como barra vertical en el borde. El esmeralda de marca queda reservado a acciones primarias y nunca se usa como color de estado.
