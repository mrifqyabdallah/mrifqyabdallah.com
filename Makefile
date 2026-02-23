# =============================================================================
# Dev setup
# =============================================================================

dev.init:
	@if [ ! -f "docker-compose.yml" ]; then \
		echo "Copying docker-compose.dev.yml to docker-compose.yml..."; \
		cp docker-compose.dev.yml docker-compose.yml; \
	else \
		echo "docker-compose.yml already exists, skipping."; \
	fi
	@if [ ! -f ".env" ]; then \
		echo "Copying .env.example to .env..."; \
		cp .env.example .env; \
	else \
		echo ".env already exists, skipping."; \
	fi
	@APP_HOST="$$(grep '^APP_HOST=' .env | cut -d '=' -f2)"; \
	APP_URL="$$(grep '^APP_URL=' .env | cut -d '=' -f2)"; \
	EXPECTED_URL="http://$$APP_HOST"; \
	if ! echo "$$APP_HOST" | grep -q '\.test$$'; then \
		echo ""; \
		echo "Error: APP_HOST must use the .test TLD (e.g. myapp.test)."; \
		echo "       .local is not supported — it causes mDNS conflicts on Windows."; \
		exit 1; \
	fi; \
	if [ "$$APP_URL" != "$$EXPECTED_URL" ]; then \
		echo ""; \
		echo "Error: APP_URL must be exactly http://$$APP_HOST"; \
		echo "       HTTPS is not supported in dev."; \
		echo "       Current APP_URL: $$APP_URL"; \
		exit 1; \
	fi
	@echo "Starting containers..."
	docker compose up --build -d
	@echo "Waiting for app container to be healthy..."
	@until [ "$$(docker inspect --format='{{.State.Health.Status}}' $$(docker compose ps -q app))" = "healthy" ]; do \
		printf "."; \
		sleep 2; \
	done
	@echo ""
	@echo "Checking app key..."
	@if [ -z "$$(grep '^APP_KEY=.\+' .env)" ]; then \
		echo "Generating app key..."; \
		docker compose exec app php artisan key:generate; \
	else \
		echo "APP_KEY already set, skipping."; \
	fi
	@echo "Running migrations..."
	docker compose exec app php artisan migrate
	@echo ""
	@echo "Waiting for Vite to be ready..."
	@until docker compose exec app curl -s http://localhost:5173 > /dev/null 2>&1; do \
		printf "."; \
		sleep 2; \
	done
	@echo ""
	@echo "✓ Done! Your app is running at $$(grep '^APP_URL=' .env | cut -d '=' -f2)"


# =============================================================================
# Docker compose
# =============================================================================

# Pass-through to docker compose
# Usage: make d <command>
# Example: make d up, make d ps, make d logs
d:
	docker compose $(filter-out $@, $(MAKECMDGOALS))

# Compound docker compose operations
d.restart:
	docker compose down
	docker compose up -d

d.rebuild:
	docker compose down
	docker compose up -d --build

d.fresh:
	@read -p "WARNING: This will wipe your database. Are you sure? [y/N] " confirm; \
	[ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ] || (echo "Aborted." && exit 1)
	docker compose down --volumes --remove-orphans
	$(MAKE) dev.init

d.logs:
	docker compose logs -f $(filter-out $@, $(MAKECMDGOALS))

# =============================================================================
# App container
# =============================================================================

# Open interactive bash shell in app container, or run a command inside
# Usage: make app bash, make app php -v
app:
	@if [ -z "$(filter-out $@, $(MAKECMDGOALS))" ]; then \
		docker compose exec app bash; \
	else \
		docker compose exec app $(filter-out $@, $(MAKECMDGOALS)); \
	fi

# Run php artisan commands
# Usage: make artisan migrate
# Usage: make artisan "migrate:fresh --seed"
artisan:
	docker compose exec app php artisan $(filter-out $@, $(MAKECMDGOALS))

# Run composer commands
# Usage: make composer install
# Usage: make composer "require some/package"
composer:
	docker compose exec app composer $(filter-out $@, $(MAKECMDGOALS))

# Run npm commands
# Usage: make npm install
# Usage: make npm "run build"
npm:
	docker compose exec app npm $(filter-out $@, $(MAKECMDGOALS))

# =============================================================================
# PostgreSQL
# =============================================================================

# Open bash in postgres container
postgres:
	docker compose exec postgres bash

# Open psql CLI directly
pg:
	docker compose exec postgres psql -U $${DB_USERNAME:-laravel} -d $${DB_DATABASE:-laravel}

# =============================================================================
# Prevent make from treating arguments as targets
# =============================================================================
%:
	@:
