---
paths:
  - 'app/Policies/**'
---

# Policies

## El rol se resuelve desde el pivote proyecto_usuario, nunca desde el equipo
La autorización lee el rol del pivote `proyecto_usuario`. **El equipo es solo un atajo de asignación masiva**: sirve para dar de alta a varias personas de golpe, pero no es la fuente de verdad de los permisos.

Resolver el rol desde el equipo produce escalada de privilegios silenciosa: alguien cambia de equipo y gana acceso a proyectos que nadie le concedió.

Al añadir a alguien a un proyecto vía equipo, materializa la fila en `proyecto_usuario` con su rol. La Policy solo mira esa tabla.

## Gana el rol más restrictivo entre el global y el de proyecto
La autorización tiene dos capas: el **rol global** (`spatie/laravel-permission`, enum `RolGlobal`) es el techo, y el **rol de proyecto** (pivote `proyecto_usuario`, enum `RolProyecto`) es el alcance. **Gana siempre el más restrictivo.**

Un `invitado` global con rol de proyecto `lider` **sigue siendo de solo lectura**. Es la regla que tapa el agujero más probable: si alguien elige mal el rol en el desplegable al dar de alta a una persona del cliente, el rol global la contiene. Funciona en los dos sentidos: un `miembro` global con rol de proyecto `cliente` tampoco ve los pagos.

Toda policy que dependa de un proyecto resuelve el rol con `App\Services\ResolverRolProyecto` (`para`, `participa`, `escribe`, `gestiona`, `veLoInterno`). No consultes el pivote a mano ni preguntes por el rol directamente: repetir esa consulta en once policies es cómo una acaba desincronizada.

`Gate::before` existe **una sola vez** y solo para `administrador`. Cualquier otro atajo convierte las policies en decorado.

## La visibilidad de lo interno la decide el rol global, no solo el de proyecto
Al preguntar "¿ve lo interno?" usa siempre `ResolverRolProyecto::veLoInterno()`, que combina las dos capas. Nunca `RolProyecto::veLoInterno()` a secas ni `$rol !== RolProyecto::Cliente`: eso deja pasar a un `invitado` global con rol de proyecto `lider`.

La policy cubre el acceso por id; los listados van por su scope. Las dos, siempre.
