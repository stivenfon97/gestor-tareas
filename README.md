# Gestor de Tareas y Periodos

Herramienta de gestión de trabajo freelance: clientes, proyectos, módulos, tareas y
periodos facturables, con su bitácora de reuniones y sus reportes en Excel. Pensada para
una sola persona (o un equipo pequeño) que necesita ver en un solo sitio en qué está
trabajando, qué le deben y qué le entregó a cada cliente.

Monolito Laravel 13 + Vue 3, unidos por Inertia 3. Corre entero en local sobre Docker: no
hace falta PHP, Node ni MySQL instalados en el host.

## Levantarlo

Requisitos: Docker Engine, el plugin `docker compose` y `make`.

```bash
cp .env.example .env          # completa APP_KEY, DB_PASSWORD y DB_ROOT_PASSWORD
make build                    # construye las imágenes app, worker y node
make up                       # levanta los cinco servicios
docker compose exec app php artisan key:generate
make migrate                  # crea las tablas
```

La aplicación queda en <http://localhost:8080>. `make help` lista todos los atajos
disponibles (`up`, `down`, `fresh`, `test`, `pint`, `shell`, `logs`, `humo`, `respaldo`,
`restaurar`…). Detalle completo —puertos, acceso desde Windows/WSL2, problemas
frecuentes— en [`docs/00-entorno.md`](docs/00-entorno.md).

## Respaldarlo

Los adjuntos, los respaldos `.sql` que suben los clientes y la base de datos entera viven
en volúmenes de Docker, fuera del control de Git.

```bash
make respaldo                 # vuelca todo a $HOME/respaldos-gestor-tareas
make restaurar ARCHIVO=...    # reconstruye desde un respaldo anterior
```

Cadencia recomendada, qué queda fuera del respaldo y el procedimiento completo de
restauración en
[`docs/00-entorno.md`](docs/00-entorno.md#respaldo-y-restauracion-make-respaldo-make-restaurar).

## Documentación

Todo lo demás vive en [`docs/`](docs/README.md), empezando por
[`docs/00-entorno.md`](docs/00-entorno.md) (el entorno) y
[`docs/01-arquitectura.md`](docs/01-arquitectura.md) (cómo está construido). El índice
completo, con el orden recomendado de lectura, está en
[`docs/README.md`](docs/README.md).

Las decisiones de diseño y sus alternativas descartadas están en
[`docs/06-historial-de-decisiones.md`](docs/06-historial-de-decisiones.md).
