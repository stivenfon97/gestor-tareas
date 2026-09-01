# Gestor de Tareas y Periodos - atajos del entorno de desarrollo.
# Todo corre dentro de Docker; no hace falta PHP ni Node en el host.

DC := docker compose
EXEC := $(DC) exec app

.DEFAULT_GOAL := help
.PHONY: help up down build fresh test pint shell logs migrate hooks humo

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

shell: ## Abre una shell dentro del contenedor app
	$(EXEC) bash

logs: ## Sigue los logs de todos los servicios
	$(DC) logs -f

hooks: ## Instala los git hooks versionados en .git/hooks
	@cp docker/git-hooks/pre-commit .git/hooks/pre-commit
	@chmod +x .git/hooks/pre-commit
	@echo "Hook pre-commit instalado: Pint verifica el formato antes de cada commit."
