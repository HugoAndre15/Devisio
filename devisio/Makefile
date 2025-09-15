# Makefile pour Devisio

.PHONY: help up down build rebuild logs shell migrate db-create db-reset test

# Couleurs pour l'affichage
GREEN=\033[0;32m
YELLOW=\033[0;33m
RED=\033[0;31m
NC=\033[0m

# Aide
help: ## Affiche cette aide
	@echo "$(GREEN)Commandes disponibles :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'

# Docker
up: ## Démarre les services
	@echo "$(GREEN)Démarrage des services...$(NC)"
	docker-compose up -d

down: ## Arrête les services
	@echo "$(RED)Arrêt des services...$(NC)"
	docker-compose down

build: ## Build les images Docker
	@echo "$(YELLOW)Build des images...$(NC)"
	docker-compose build

rebuild: ## Rebuild complet (sans cache)
	@echo "$(YELLOW)Rebuild complet...$(NC)"
	docker-compose down -v
	docker-compose build --no-cache
	docker-compose up -d

logs: ## Affiche les logs
	docker-compose logs -f

logs-app: ## Affiche les logs de l'application
	docker-compose logs -f app

shell: ## Ouvre un shell dans le conteneur app
	docker-compose exec app bash

# Base de données
db-create: ## Crée la base de données
	@echo "$(GREEN)Création de la base de données...$(NC)"
	docker-compose exec app php bin/console doctrine:database:create --if-not-exists

db-reset: ## Recrée la base de données (ATTENTION: supprime toutes les données)
	@echo "$(RED)Réinitialisation de la base de données...$(NC)"
	docker-compose exec app php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec app php bin/console doctrine:database:create
	$(MAKE) migrate

# Migrations
migrate: ## Exécute les migrations
	@echo "$(GREEN)Exécution des migrations...$(NC)"
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

migrate-status: ## Affiche le statut des migrations
	docker-compose exec app php bin/console doctrine:migrations:status

migrate-rollback: ## Annule la dernière migration
	@echo "$(YELLOW)Rollback de la dernière migration...$(NC)"
	docker-compose exec app php bin/console doctrine:migrations:migrate prev --no-interaction

migrate-diff: ## Génère une migration basée sur les différences d'entités
	docker-compose exec app php bin/console doctrine:migrations:diff

# Installation
install: ## Installation complète du projet
	@echo "$(GREEN)Installation complète du projet...$(NC)"
	$(MAKE) build
	$(MAKE) up
	sleep 10
	$(MAKE) db-create
	$(MAKE) migrate
	@echo "$(GREEN)Installation terminée ! L'application est accessible sur http://localhost:8000$(NC)"

# Nettoyage
clean: ## Nettoie les conteneurs et volumes
	@echo "$(RED)Nettoyage...$(NC)"
	docker-compose down -v --remove-orphans
	docker system prune -f

# Cache Symfony
cache-clear: ## Vide le cache Symfony
	docker-compose exec app php bin/console cache:clear

# Assets
assets-build: ## Build les assets
	docker-compose exec app npm run build

assets-watch: ## Watch les assets
	docker-compose exec app npm run watch

# Tests (pour plus tard)
test: ## Lance les tests
	docker-compose exec app php bin/phpunit

# Commandes rapides
start: up ## Alias pour up
stop: down ## Alias pour down
restart: down up ## Redémarre les services
