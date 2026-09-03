# Gestor de Tareas y Periodos - atajos del entorno de desarrollo.
# Todo corre dentro de Docker; no hace falta PHP ni Node en el host.

DC := docker compose
EXEC := $(DC) exec app

# Fuera del repositorio a propósito: un volcado dentro del árbol de trabajo
# acaba en un commit tarde o temprano. Se puede sobrescribir por invocación
# (`make respaldo RESPALDOS_DIR=/mnt/backups`) o exportándola antes.
RESPALDOS_DIR ?= $(HOME)/respaldos-gestor-tareas

.DEFAULT_GOAL := help
.PHONY: help up down build fresh test pint formato lint shell logs migrate hooks humo respaldo restaurar

help: ## Muestra esta ayuda
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

up: ## Levanta los cinco servicios en segundo plano
	$(DC) up -d

down: ## Detiene los servicios (los volumenes nombrados sobreviven)
	$(DC) down

build: ## Reconstruye las imagenes app y worker
	$(DC) build

fresh: ## Recrea la base de datos y ejecuta los seeders
	$(EXEC) php artisan migrate:fresh --seed

migrate: ## Ejecuta las migraciones pendientes
	$(EXEC) php artisan migrate

test: ## Ejecuta la suite de pruebas
	$(EXEC) php artisan test

# Corre contra los assets compilados, no contra el dev server: la URL que Vite
# escribe en public/hot es localhost:5173, que dentro de la red de Compose es el
# propio contenedor de la comprobacion. Ademas es la version que vale, porque el
# escaner de Tailwind solo falla en el build de produccion.
humo: ## Comprobacion de humo con Playwright (necesita los servicios arriba)
	$(DC) exec -T node npm run build
	@mv public/hot public/hot.pausado 2>/dev/null || true; \
		$(DC) --profile humo run --rm humo; estado=$$?; \
		mv public/hot.pausado public/hot 2>/dev/null || true; \
		echo "Capturas en storage/humo/capturas/"; \
		exit $$estado

pint: ## Formatea el codigo PHP
	$(EXEC) vendor/bin/pint

# Pint y Prettier no se pisan: Pint solo toca .php y el alcance de Prettier son
# .ts, .vue, .json y .css. Blade queda fuera de los dos, porque Prettier no
# tiene parser para .blade.php.
formato: ## Formatea el frontend (Prettier)
	$(DC) exec node npm run format

verificar: ## Comprueba formato y estilo de backend y frontend, sin tocar nada
	$(EXEC) vendor/bin/pint --test
	$(DC) exec -T node npm run format:check
	$(DC) exec -T node npm run lint:check

shell: ## Abre una shell dentro del contenedor app
	$(EXEC) bash

logs: ## Sigue los logs de todos los servicios
	$(DC) logs -f

hooks: ## Instala los git hooks versionados en .git/hooks
	@cp docker/git-hooks/pre-commit .git/hooks/pre-commit
	@chmod +x .git/hooks/pre-commit
	@echo "Hook pre-commit instalado: Pint verifica el formato antes de cada commit."

# Con los servicios arriba, sin tumbar nada: mysqldump lee con
# --single-transaction (no bloquea escrituras en tablas InnoDB) y los archivos
# se leen del propio volumen en caliente. Todo el trabajo intermedio pasa por
# un directorio temporal del host (`mktemp -d`, fuera del repo y fuera de los
# volúmenes), nunca por `storage/` del proyecto.
#
# Un solo .tar.gz con dos miembros (`basedatos.sql.gz`, `archivos.tar.gz`) y
# no dos archivos sueltos: es la unidad que de verdad hay que conservar junta
# -restaurar solo la base sin los archivos dejaría referencias a adjuntos que
# ya no existen, y al revés, archivos sin las filas que los describen-.
#
# Sin pipes entre `docker compose exec` y el comando que procesa su salida:
# con un pipe, el estado de salida que ve `&&` es el del último comando de la
# tubería (`gzip`, `tar`), no el de `mysqldump` o el `tar` de dentro del
# contenedor, así que un volcado que falla a medias podría darse por bueno.
# Todo pasa por redirección de archivo (`>`, `<`), cuyo estado de salida sí es
# el del comando real.
respaldo: ## Vuelca la base de datos y empaqueta los archivos subidos en un solo .tar.gz fechado (servicios arriba, sin tumbar nada)
	@mkdir -p "$(RESPALDOS_DIR)"; \
		fecha=$$(date +%Y-%m-%d_%H%M%S); \
		tmp=$$(mktemp -d); \
		( echo "Volcando la base de datos..." && \
		  $(DC) exec -T mysql sh -c 'MYSQL_PWD="$$MYSQL_ROOT_PASSWORD" mysqldump --single-transaction --routines --triggers -P 3308 -u root "$$MYSQL_DATABASE"' > "$$tmp/basedatos.sql" && \
		  gzip "$$tmp/basedatos.sql" && \
		  echo "Empaquetando los archivos subidos..." && \
		  $(DC) exec -T app tar -czf - -C storage/app . > "$$tmp/archivos.tar.gz" && \
		  tar -czf "$(RESPALDOS_DIR)/respaldo_$$fecha.tar.gz" -C "$$tmp" basedatos.sql.gz archivos.tar.gz \
		); estado=$$?; \
		rm -rf "$$tmp"; \
		if [ $$estado -eq 0 ]; then \
			echo "Respaldo listo: $(RESPALDOS_DIR)/respaldo_$$fecha.tar.gz"; \
		else \
			echo "El respaldo falló; no quedó ningún archivo a medias." >&2; \
		fi; \
		exit $$estado

# Reconstruye los dos volúmenes desde un archivo de `make respaldo`. Destruye
# lo que hubiera antes en la base de datos y en los archivos subidos -es una
# restauración, no una fusión-, igual que `make fresh` con la base de datos.
restaurar: ## Reconstruye la base de datos y los archivos desde un respaldo: make restaurar ARCHIVO=ruta/al/respaldo_FECHA.tar.gz
	@if [ -z "$(ARCHIVO)" ]; then \
			echo "Uso: make restaurar ARCHIVO=ruta/al/respaldo_FECHA.tar.gz" >&2; exit 1; \
		fi; \
		if [ ! -f "$(ARCHIVO)" ]; then \
			echo "No existe el archivo: $(ARCHIVO)" >&2; exit 1; \
		fi; \
		tmp=$$(mktemp -d); \
		( tar -xzf "$(ARCHIVO)" -C "$$tmp" && \
		  gunzip "$$tmp/basedatos.sql.gz" && \
		  echo "Restaurando la base de datos..." && \
		  $(DC) exec -T mysql sh -c 'MYSQL_PWD="$$MYSQL_ROOT_PASSWORD" mysql -P 3308 -u root "$$MYSQL_DATABASE"' < "$$tmp/basedatos.sql" && \
		  echo "Restaurando los archivos subidos..." && \
		  $(DC) exec -T app sh -c 'find storage/app -mindepth 1 -delete' && \
		  $(DC) exec -T app tar -xzf - -C storage/app < "$$tmp/archivos.tar.gz" \
		); estado=$$?; \
		rm -rf "$$tmp"; \
		if [ $$estado -eq 0 ]; then \
			echo "Restauración completa: base de datos y archivos desde $(ARCHIVO)."; \
		else \
			echo "La restauración falló a medias; puede que la base de datos o los archivos hayan quedado incompletos. Reintenta." >&2; \
		fi; \
		exit $$estado
