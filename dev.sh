#!/bin/bash
set -e  # Dừng nếu có lỗi

# =============================
# COLORS
# =============================
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()   { echo -e "${GREEN}✅ $1${NC}"; }
warn()  { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }

# =============================
# BUILD & START
# =============================
log "Khởi động Docker containers..."
docker compose up -d --build

# =============================
# ĐỢI MySQL HEALTHY
# =============================
log "Đợi MySQL sẵn sàng..."
until docker exec lms-db mysqladmin ping -h localhost --silent 2>/dev/null; do
    echo -n "."
    sleep 2
done
echo ""
log "MySQL đã sẵn sàng"

# =============================
# LARAVEL SETUP
# =============================
log "Clear Laravel cache..."
docker exec lms-app php artisan optimize:clear
docker exec lms-app php artisan config:clear
docker exec lms-app php artisan cache:clear
docker exec lms-app php artisan route:clear
docker exec lms-app php artisan view:clear

log "Chạy migration..."
docker exec lms-app php artisan migrate --force

# =============================
# ĐỢI REVERB
# =============================
log "Đợi Reverb khởi động..."
MAX_WAIT=30
COUNT=0
until docker exec lms-app curl -s http://lms-reverb:8080/apps/123456 > /dev/null 2>&1; do
    echo -n "."
    sleep 2
    COUNT=$((COUNT + 2))
    if [ $COUNT -ge $MAX_WAIT ]; then
        warn "Reverb chưa phản hồi sau ${MAX_WAIT}s, kiểm tra log:"
        docker logs lms-reverb --tail=20
        break
    fi
done
echo ""

# =============================
# HEALTH CHECK
# =============================
log "Kiểm tra services..."

# App
docker exec lms-app curl -s http://localhost/up > /dev/null \
    && log "Laravel OK" \
    || warn "Laravel chưa phản hồi"

# Reverb từ host
curl -s http://localhost:8080/apps/123456 > /dev/null \
    && log "Reverb (host) OK" \
    || warn "Reverb (host) chưa phản hồi"

# Reverb từ app container
docker exec lms-app curl -s http://lms-reverb:8080/apps/123456 > /dev/null \
    && log "Reverb (internal) OK" \
    || warn "Reverb (internal) chưa phản hồi"

# Queue worker
docker compose ps --status running --services | grep -qx 'queue-worker' \
    && log "Queue worker OK" \
    || { warn "Queue worker chưa chạy, log gần nhất:"; docker compose logs --tail=30 queue-worker; }

# =============================
# THÔNG TIN
# =============================
echo ""
echo -e "${GREEN}==============================${NC}"
echo -e "${GREEN}  SmartLMS đã sẵn sàng! 🚀  ${NC}"
echo -e "${GREEN}==============================${NC}"
echo -e "  🌐 App:    https://smartlms.io.vn"
echo -e "  🔌 WS:     wss://ws.smartlms.io.vn"
echo -e "  🗄️  MySQL:  localhost:3306"
echo -e "  🐘 PgSQL:  localhost:5432"
echo -e "  ⚙️  Queue:  ai, documents, default"
echo ""

# =============================
# CLOUDFLARE TUNNEL
# =============================
log "Khởi động Cloudflare Tunnel..."
cloudflared tunnel run lms-docker
