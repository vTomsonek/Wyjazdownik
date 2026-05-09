#!/bin/bash
#
# Wyjazdownik.pl - deployment przez rsync.
#
# Uruchamiaj z lokalnego XAMPP'a (lub WSL na Windows):
#   ./deploy.sh
#
# Wymaga skonfigurowanego SSH key auth do VPS (ssh-copy-id).
# Konfiguracja - edytuj zmienne ponizej.

set -e

# ============================================================================
# CONFIG - edytuj zanim uruchomisz
# ============================================================================
REMOTE_USER="root"
REMOTE_HOST="vpsXXXXX.ovh.net"
REMOTE_PATH="/var/www/wyjazdownik"
LOCAL_PATH="$(cd "$(dirname "$0")" && pwd)"

# ============================================================================
# Pliki/foldery NIE pushowane na produkcje
# ============================================================================
EXCLUDES=(
    --exclude=".git"
    --exclude=".gitignore"
    --exclude=".idea"
    --exclude=".vscode"
    --exclude=".env"
    --exclude="vendor"
    --exclude="storage/sent_emails.log"
    --exclude="public/assets/uploads/banners/*"
    --exclude="public/assets/uploads/avatars/*"
    --exclude="docs"
    --exclude="deploy.sh"
    --exclude="*.bak"
    --exclude="*.swp"
    --exclude="*.tmp"
    --exclude=".DS_Store"
    --exclude="Thumbs.db"
    --exclude="wyjazdownik-final-prompt.md"
)

echo "================================================================"
echo " Wyjazdownik.pl - deployment do ${REMOTE_USER}@${REMOTE_HOST}"
echo " Local:  ${LOCAL_PATH}"
echo " Remote: ${REMOTE_PATH}"
echo "================================================================"

# 1. Dry run - pokaz co zostanie zmienione
echo ""
echo "[1/4] Dry run - co zostanie wyslane:"
rsync -avhn --delete "${EXCLUDES[@]}" \
    "${LOCAL_PATH}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/" \
    | tail -20

read -p "Kontynuowac? [y/N] " confirm
if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
    echo "Przerwano."
    exit 0
fi

# 2. Faktyczny rsync
echo ""
echo "[2/4] Wysylam pliki..."
rsync -avh --delete "${EXCLUDES[@]}" \
    "${LOCAL_PATH}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"

# 3. Composer install (na produkcji, --no-dev)
echo ""
echo "[3/4] Composer install na produkcji..."
ssh "${REMOTE_USER}@${REMOTE_HOST}" "cd ${REMOTE_PATH} && composer install --no-dev --optimize-autoloader --no-interaction"

# 4. Permissions + cache cleanup
echo ""
echo "[4/4] Ustawiam permissions..."
ssh "${REMOTE_USER}@${REMOTE_HOST}" "
    chown -R www-data:www-data ${REMOTE_PATH} && \
    find ${REMOTE_PATH} -type d -exec chmod 755 {} \; && \
    find ${REMOTE_PATH} -type f -exec chmod 644 {} \; && \
    chmod 755 ${REMOTE_PATH}/cron/cleanup.php && \
    chmod 750 ${REMOTE_PATH}/.env 2>/dev/null || true
"

echo ""
echo "================================================================"
echo " DEPLOY ZAKONCZONY"
echo "================================================================"
echo ""
echo "Co zrobic dalej (TYLKO PRZY PIERWSZYM DEPLOYU):"
echo "  1. SSH na serwer:    ssh ${REMOTE_USER}@${REMOTE_HOST}"
echo "  2. Edytuj .env:      nano ${REMOTE_PATH}/.env"
echo "  3. Uruchom install:  cd ${REMOTE_PATH} && php install.php --seed=no"
echo "  4. Skonfiguruj Apache VirtualHost (zobacz docs/apache-vhost.conf.example)"
echo "  5. Restartuj Apache: sudo systemctl reload apache2"
echo "  6. Dorzuc cron:      zobacz docs/deployment.md"
