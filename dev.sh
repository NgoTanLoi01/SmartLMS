#!/bin/bash

# 1. Khởi động các container Docker (nếu chưa chạy)
echo "🚀 Đang khởi động Docker containers..."
docker-compose up -d

# 2. Đợi 2 giây để đảm bảo container lms-app đã sẵn sàng
sleep 2

# 3. Chạy Laravel Reverb ở chế độ chạy ngầm (-d)
echo "📡 Đang khởi động Laravel Reverb (Background)..."
docker exec -d lms-app php artisan reverb:start --host=0.0.0.0 --port=8080

# 4. Thông báo cho người dùng
echo "✅ Hệ thống Local đã sẵn sàng tại http://localhost:8000"

echo "🌐 Đang mở hầm Cloudflare Tunnel (smartlms.io.vn)..."

echo "💡 (Bấm Ctrl + C để dừng Tunnel khi kết thúc làm việc)"

# 5. Chạy Cloudflare Tunnel (Lệnh này sẽ giữ Terminal để thầy theo dõi log)
cloudflared tunnel run --url http://localhost:8000 lms-docker
