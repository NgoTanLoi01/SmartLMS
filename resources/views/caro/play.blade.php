@extends('layouts.app')

@section('content')
    <div class="container py-4 text-center">
        <div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 700px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Phòng Caro: <span class="text-primary">{{ $roomId }}</span></h4>
                <div id="status-badge" class="badge bg-secondary rounded-pill px-3 py-2">Đang đợi đối thủ...</div>
            </div>

            <div id="caro-board" class="mx-auto shadow-sm"
                style="display: grid; grid-template-columns: repeat(15, 1fr); width: 100%; max-width: 500px; aspect-ratio: 1/1; border: 2px solid #333; background: #e9ecef;">
            </div>

            <div class="mt-4">
                <p id="turn-info" class="fw-bold fs-5 text-primary">Đang khởi tạo...</p>
                {{-- <button class="btn btn-outline-secondary rounded-pill px-4" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Làm mới
                </button> --}}
                <a href="{{ route('tools.caro.index') }}" class="btn btn-light rounded-pill px-4">Thoát phòng</a>
            </div>
        </div>
    </div>

    <style>
        /* Cố định kích thước bàn cờ 15x15 vuông vức */
        #caro-board {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            grid-template-rows: repeat(15, 1fr);
            /* Cố định luôn cả chiều cao của hàng */
            width: 100%;
            max-width: 550px;
            /* Kích thước tối đa của bàn cờ */
            aspect-ratio: 1 / 1;
            /* Ép buộc tỷ lệ vuông 1:1 */
            border: 2px solid #333;
            background: #e9ecef;
            box-sizing: border-box;
        }

        /* Định dạng từng ô cờ không bị vỡ */
        .caro-cell {
            width: 100%;
            height: 100%;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: calc(15px + 0.5vw);
            /* Chữ tự co giãn theo màn hình nhưng không quá to */
            font-weight: bold;
            cursor: pointer;
            background: #fff;
            user-select: none;
            box-sizing: border-box;
            overflow: hidden;
            /* Quan trọng: Ngăn chữ đẩy ô cờ phình to ra */
            line-height: 1;
            margin: 0;
            padding: 0;
        }

        .caro-cell:hover {
            background: #f8f9fa;
        }

        .cell-x {
            color: #0d6efd;
        }

        /* Màu Xanh cho X */
        .cell-o {
            color: #dc3545;
        }

        /* Màu Đỏ cho O */
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>

    <script>
        window.onload = function() {
            // 1. Cấu hình Axios & CSRF
            window.axios = axios;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');

            // 2. Cấu hình Reverb
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ env('REVERB_APP_KEY') }}',
                wsHost: '{{ env('REVERB_HOST') }}',
                wsPort: 443,
                wssPort: 443,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
            });

            // Biến Game
            let roomId = "{{ $roomId }}";
            let currentUserId = {{ auth()->id() ?? 'null' }};
            let mySymbol = 'X';
            let currentTurn = 'X';
            let isGameActive = false;
            let boardState = Array(15).fill().map(() => Array(15).fill(null));

            const boardEl = document.getElementById('caro-board');
            const turnInfo = document.getElementById('turn-info');
            const badge = document.getElementById('status-badge');

            // 3. Vẽ bàn cờ 15x15
            for (let r = 0; r < 15; r++) {
                for (let c = 0; c < 15; c++) {
                    let cell = document.createElement('div');
                    cell.className = 'caro-cell';
                    cell.dataset.row = r;
                    cell.dataset.col = c;
                    cell.onclick = () => makeMove(r, c, true);
                    boardEl.appendChild(cell);
                }
            }

            function updateTurnText() {
                if (!isGameActive) return;
                if (currentTurn === mySymbol) {
                    turnInfo.innerHTML =
                        `<span class="${mySymbol === 'X' ? 'text-primary' : 'text-danger'}">Tới lượt bạn (${mySymbol})</span>`;
                } else {
                    turnInfo.innerHTML =
                        `<span class="text-muted">Chờ đối thủ (${currentTurn === 'X' ? 'X' : 'O'})...</span>`;
                }
            }

            // 4. Logic Đi quân
            function makeMove(r, c, isLocal) {
                // Chặn nếu: game chưa bắt đầu / ô đã đánh / chưa tới lượt mình
                if (!isGameActive || boardState[r][c] || (isLocal && currentTurn !== mySymbol)) return;

                // Đánh dấu ô
                boardState[r][c] = currentTurn;
                let cell = boardEl.children[r * 15 + c];
                cell.innerText = currentTurn;
                cell.classList.add(currentTurn === 'X' ? 'cell-x' : 'cell-o');

                // Gửi lên server nếu là mình đánh
                if (isLocal) {
                    axios.post(`/tools/caro/${roomId}/move`, {
                            move: {
                                r,
                                c,
                                symbol: mySymbol
                            }
                        })
                        .catch(err => console.error("Lỗi gửi Caro:", err));
                }

                // Kiểm tra thắng
                if (checkWin(r, c)) {
                    isGameActive = false;
                    let winnerText = currentTurn === mySymbol ? 'Bạn đã giành chiến thắng!' : 'Đối thủ đã chiến thắng!';
                    Swal.fire({
                        title: 'Trận đấu kết thúc!',
                        text: winnerText,
                        icon: currentTurn === mySymbol ? 'success' : 'error',
                        confirmButtonText: 'Thoát trận',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        window.location.href = "/tools/caro";
                    });
                    return;
                }

                // Đổi lượt
                currentTurn = currentTurn === 'X' ? 'O' : 'X';
                updateTurnText();
            }

            function checkWin(r, c) {
                const directions = [
                    [0, 1],
                    [1, 0],
                    [1, 1],
                    [1, -1]
                ];
                const symbol = boardState[r][c];
                for (let [dr, dc] of directions) {
                    let count = 1;
                    for (let i = 1; i < 5; i++) {
                        let nr = r + dr * i,
                            nc = c + dc * i;
                        if (nr >= 0 && nr < 15 && nc >= 0 && nc < 15 && boardState[nr][nc] === symbol) count++;
                        else break;
                    }
                    for (let i = 1; i < 5; i++) {
                        let nr = r - dr * i,
                            nc = c - dc * i;
                        if (nr >= 0 && nr < 15 && nc >= 0 && nc < 15 && boardState[nr][nc] === symbol) count++;
                        else break;
                    }
                    if (count >= 5) return true;
                }
                return false;
            }

            // 5. Kết nối Echo
            window.Echo.join(`caro.${roomId}`)
                .here(users => {
                    const otherUsers = users.filter(u => u.id !== currentUserId);
                    if (otherUsers.length > 0) {
                        mySymbol = 'O'; // Người vào sau là O
                        console.log("Bạn là O");
                    } else {
                        mySymbol = 'X'; // Người vào trước là X
                        console.log("Bạn là X");
                    }

                    if (users.length >= 2) {
                        isGameActive = true;
                        badge.className = "badge bg-success rounded-pill px-3 py-2";
                        badge.innerText = "Trận đấu bắt đầu";
                        updateTurnText();
                    }
                })
                .joining(user => {
                    isGameActive = true;
                    badge.className = "badge bg-success rounded-pill px-3 py-2";
                    badge.innerText = "Trận đấu bắt đầu";
                    updateTurnText();
                })
                .leaving(user => {
                    isGameActive = false;
                    badge.className = "badge bg-secondary rounded-pill px-3 py-2";
                    badge.innerText = "Đối thủ đã thoát";
                    turnInfo.innerText = "Trận đấu tạm dừng";
                })
                .listen('.CaroMoveMade', (e) => {
                    console.log("Nhận nước đi:", e);
                    makeMove(e.move.r, e.move.c, false);
                });
        };
    </script>
@endsection
