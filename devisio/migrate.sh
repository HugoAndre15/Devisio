#!/bin/bash

# Script pour gérer les migrations Doctrine dans le conteneur Docker

set -e

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages colorés
echo_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

echo_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

echo_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

echo_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier que Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    echo_error "Docker Compose n'est pas installé ou accessible."
    exit 1
fi

# Vérifier que les conteneurs sont en cours d'exécution
if ! docker-compose ps | grep -q "Up"; then
    echo_warning "Les conteneurs ne semblent pas être en cours d'exécution."
    echo_info "Démarrage des conteneurs..."
    docker-compose up -d
    sleep 10
fi

# Fonction pour exécuter une commande dans le conteneur app
run_in_container() {
    docker-compose exec app "$@"
}

# Menu principal
show_menu() {
    echo
    echo_info "=== Gestionnaire de Migrations Devisio ==="
    echo "1. Vérifier le statut des migrations"
    echo "2. Exécuter les migrations"
    echo "3. Rollback (annuler la dernière migration)"
    echo "4. Créer une nouvelle migration"
    echo "5. Créer la base de données"
    echo "6. Supprimer et recréer la base de données"
    echo "7. Afficher l'aide Doctrine"
    echo "8. Shell dans le conteneur"
    echo "9. Quitter"
    echo
}

# Vérifier le statut des migrations
check_migrations() {
    echo_info "Vérification du statut des migrations..."
    run_in_container php bin/console doctrine:migrations:status
}

# Exécuter les migrations
run_migrations() {
    echo_info "Exécution des migrations..."
    run_in_container php bin/console doctrine:migrations:migrate --no-interaction
    echo_success "Migrations exécutées avec succès !"
}

# Rollback de la dernière migration
rollback_migration() {
    echo_warning "Annulation de la dernière migration..."
    echo "Êtes-vous sûr ? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        run_in_container php bin/console doctrine:migrations:migrate prev --no-interaction
        echo_success "Rollback effectué avec succès !"
    else
        echo_info "Rollback annulé."
    fi
}

# Créer une nouvelle migration
create_migration() {
    echo_info "Création d'une nouvelle migration..."
    run_in_container php bin/console doctrine:migrations:generate
    echo_success "Migration créée avec succès ! Pensez à la renommer si nécessaire."
}

# Créer la base de données
create_database() {
    echo_info "Création de la base de données..."
    run_in_container php bin/console doctrine:database:create --if-not-exists
    echo_success "Base de données créée avec succès !"
}

# Supprimer et recréer la base de données
reset_database() {
    echo_warning "ATTENTION: Cette action va supprimer toutes les données de la base !"
    echo "Êtes-vous sûr ? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo_info "Suppression de la base de données..."
        run_in_container php bin/console doctrine:database:drop --force --if-exists
        echo_info "Création de la base de données..."
        run_in_container php bin/console doctrine:database:create
        echo_info "Exécution des migrations..."
        run_in_container php bin/console doctrine:migrations:migrate --no-interaction
        echo_success "Base de données réinitialisée avec succès !"
    else
        echo_info "Réinitialisation annulée."
    fi
}

# Afficher l'aide Doctrine
show_doctrine_help() {
    echo_info "Aide Doctrine Migrations :"
    run_in_container php bin/console list doctrine:migrations
}

# Shell dans le conteneur
container_shell() {
    echo_info "Ouverture d'un shell dans le conteneur..."
    docker-compose exec app bash
}

# Boucle principale
while true; do
    show_menu
    echo -n "Choisissez une option (1-9): "
    read -r choice
    
    case $choice in
        1)
            check_migrations
            ;;
        2)
            run_migrations
            ;;
        3)
            rollback_migration
            ;;
        4)
            create_migration
            ;;
        5)
            create_database
            ;;
        6)
            reset_database
            ;;
        7)
            show_doctrine_help
            ;;
        8)
            container_shell
            ;;
        9)
            echo_info "Au revoir !"
            exit 0
            ;;
        *)
            echo_error "Option invalide. Veuillez choisir entre 1 et 9."
            ;;
    esac
    
    echo
    echo "Appuyez sur Entrée pour continuer..."
    read -r
done
