@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0 text-primary">Phòng: {{ $roomId }}</h4>
                        <div id="status-badge" class="badge bg-secondary rounded-pill px-3 py-2">Đang đợi đối thủ...</div>
                    </div>

                    <div id="myBoard" class="mx-auto" style="width: 100%; max-width: 550px; min-height: 400px;"></div>

                    <div class="mt-4">
                        <a href="{{ route('tools.chess.index') }}" class="btn btn-light rounded-pill px-4">Thoát phòng</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-history me-2"></i>Lịch sử nước đi</h6>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-hover text-center" id="historyTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Trắng</th>
                                    <th>Đen</th>
                                </tr>
                            </thead>
                            <tbody id="move-history-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <h6 class="fw-bold text-success mb-3">
                        <i class="fas fa-book-open me-2"></i>Hướng dẫn chơi cờ vua
                    </h6>

                    <div class="accordion accordion-flush" id="chessRules">

                        <!-- Mục tiêu -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#rule1">
                                    1. Mục tiêu trò chơi
                                </button>
                            </h2>

                            <div id="rule1" class="accordion-collapse collapse" data-bs-parent="#chessRules">
                                <div class="accordion-body small text-muted">
                                    Mục tiêu của cờ vua là <b>chiếu bí Vua đối phương</b>.
                                    Điều này xảy ra khi Vua bị tấn công và không còn nước đi hợp lệ để thoát.
                                    Khi một bên bị chiếu bí, ván cờ kết thúc.
                                </div>
                            </div>
                        </div>

                        <!-- Luật di chuyển -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#rule2">
                                    2. Cách di chuyển các quân cờ
                                </button>
                            </h2>

                            <div id="rule2" class="accordion-collapse collapse" data-bs-parent="#chessRules">
                                <div class="accordion-body small text-muted">
                                    <b>Tốt:</b> Đi thẳng 1 ô, nước đầu có thể đi 2 ô; ăn quân theo đường chéo.<br><br>

                                    <b>Xe:</b> Di chuyển theo hàng ngang hoặc cột dọc không giới hạn số ô.<br><br>

                                    <b>Tượng:</b> Di chuyển chéo không giới hạn số ô.<br><br>

                                    <b>Mã:</b> Di chuyển theo hình chữ L và có thể nhảy qua quân khác.<br><br>

                                    <b>Hậu:</b> Kết hợp cách đi của Xe và Tượng, là quân mạnh nhất trên bàn cờ.<br><br>

                                    <b>Vua:</b> Di chuyển 1 ô theo mọi hướng và là quân quan trọng nhất.
                                </div>
                            </div>
                        </div>

                        <!-- Luật đặc biệt -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#rule3">
                                    3. Luật đặc biệt
                                </button>
                            </h2>

                            <div id="rule3" class="accordion-collapse collapse" data-bs-parent="#chessRules">
                                <div class="accordion-body small text-muted">
                                    <b>Nhập thành:</b> Vua và Xe được di chuyển cùng lúc nếu chưa từng di chuyển và không có
                                    quân cản.<br><br>

                                    <b>Phong cấp:</b> Khi Tốt đi đến hàng cuối cùng của bàn cờ, có thể phong thành Hậu, Xe,
                                    Tượng hoặc Mã.<br><br>

                                    <b>Bắt tốt qua đường:</b> Tốt có thể ăn Tốt đối phương ngay sau khi đối phương đi 2 ô ở
                                    nước đầu tiên.
                                </div>
                            </div>
                        </div>

                        <!-- Kết quả -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#rule4">
                                    4. Kết thúc ván cờ
                                </button>
                            </h2>

                            <div id="rule4" class="accordion-collapse collapse" data-bs-parent="#chessRules">
                                <div class="accordion-body small text-muted">
                                    Ván cờ có thể kết thúc bằng:
                                    <ul class="mb-0 mt-2">
                                        <li><b>Chiếu bí:</b> Một bên thắng.</li>
                                        <li><b>Hòa cờ:</b> Không bên nào thắng.</li>
                                        <li><b>Đầu hàng:</b> Một người chơi chấp nhận thua.</li>
                                        <li><b>Hết thời gian:</b> Người hết giờ sẽ thua.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>

    <script>
        window.onload = function() {
            window.axios = axios;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');

            var game = new Chess();
            var roomId = "{{ $roomId }}";
            var myColor = 'white'; // Khởi tạo mặc định
            var currentUserId = {{ auth()->id() ?? 'null' }};

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

            function updateStatus(count) {
                const badge = document.getElementById('status-badge');
                if (badge) {
                    badge.className = count >= 2 ? "badge bg-success rounded-pill px-3 py-2" :
                        "badge bg-secondary rounded-pill px-3 py-2";
                    badge.innerHTML = count >= 2 ? "Trận đấu bắt đầu!" : "Đang đợi đối thủ...";
                }
            }

            function updateMoveHistory() {
                const history = game.history();
                const tbody = document.getElementById('move-history-body');
                if (!tbody) return;

                tbody.innerHTML = '';
                for (let i = 0; i < history.length; i += 2) {
                    const moveNumber = (i / 2) + 1;
                    const whiteMove = history[i];
                    const blackMove = history[i + 1] ? history[i + 1] : '';
                    tbody.innerHTML += `
                    <tr>
                        <td class="text-muted">${moveNumber}</td>
                        <td class="fw-bold text-primary">${whiteMove}</td>
                        <td class="fw-bold text-danger">${blackMove}</td>
                    </tr>`;
                }
                const tableContainer = tbody.parentElement.parentElement;
                tableContainer.scrollTop = tableContainer.scrollHeight;
            }

            // --- HÀM KIỂM TRA & HIỂN THỊ CHIẾN THẮNG ---
            function checkGameOver() {
                if (game.game_over()) {
                    let title = 'Trò chơi kết thúc!';
                    let text = '';
                    let icon = 'info';

                    if (game.in_checkmate()) {
                        // Nếu chiếu bí, người vừa đi nước cuối cùng (được lưu trong game.turn()) là NGƯỜI THUA
                        // Vậy người thắng là người KHÔNG PHẢI lượt hiện tại
                        let winner = game.turn() === 'w' ? 'Quân Đen' : 'Quân Trắng';
                        title = 'Chiếu Bí!';
                        text = `Chúc mừng ${winner} đã giành chiến thắng! 🏆`;
                        icon = 'success';
                    } else if (game.in_draw()) {
                        title = 'Hòa Cờ!';
                        text = 'Hai bên bất phân thắng bại. 🤝';
                    } else if (game.in_stalemate()) {
                        title = 'Hết Nước Đi!';
                        text = 'Ván cờ kết thúc hòa. 🤝';
                    } else if (game.in_threefold_repetition()) {
                        title = 'Hòa Cờ!';
                        text = 'Lặp lại nước đi 3 lần. 🔄';
                    }

                    // Hiển thị thông báo đẹp mắt bằng SweetAlert2
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        confirmButtonText: 'Chơi lại',
                        confirmButtonColor: '#0d6efd',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
            }

            const boardEl = document.getElementById('myBoard');
            if (boardEl) {
                var board = Chessboard('myBoard', {
                    draggable: true,
                    position: 'start',
                    pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',

                    onDragStart: function(source, piece, position, orientation) {
                        if (game.game_over()) return false;

                        // CHỈ CHO PHÉP ĐI QUÂN CỦA MÌNH
                        // 'piece' có dạng 'wP', 'bN'... Ký tự đầu tiên là màu ('w' hoặc 'b')
                        // 'myColor' là 'white' hoặc 'black'
                        if ((myColor === 'white' && piece.charAt(0) !== 'w') ||
                            (myColor === 'black' && piece.charAt(0) !== 'b')) {
                            return false;
                        }

                        // CHỈ CHO PHÉP ĐI ĐÚNG LƯỢT
                        if ((game.turn() === 'w' && myColor !== 'white') ||
                            (game.turn() === 'b' && myColor !== 'black')) {
                            return false;
                        }
                    },

                    onDrop: function(source, target) {
                        var move = game.move({
                            from: source,
                            to: target,
                            promotion: 'q'
                        });
                        if (move === null) return 'snapback';

                        updateMoveHistory();
                        checkGameOver(); // Kiểm tra kết thúc sau khi mình đi

                        window.axios.post(`/tools/chess/${roomId}/move`, {
                                move: move
                            })
                            .catch(error => console.error("Lỗi gửi nước đi:", error));
                    }
                });

                setTimeout(() => {
                    board.resize();
                }, 500);
            }

            // KẾT NỐI & PHÂN PHE
            window.Echo.join(`chess.${roomId}`)
                .here((users) => {
                    console.log("👥 Danh sách:", users);

                    // Xác định phe: Nếu trong phòng CÓ người VÀ người đó KHÁC mình -> mình vào sau -> Quân Đen
                    const otherUsers = users.filter(u => u.id !== currentUserId);
                    if (otherUsers.length > 0) {
                        myColor = 'black';
                        board.orientation('black');
                        console.log("⚫ Cầm quân ĐEN");
                    } else {
                        myColor = 'white';
                        board.orientation('white');
                        console.log("⚪ Cầm quân TRẮNG");
                    }

                    updateStatus(users.length);
                })
                .joining((user) => {
                    console.log(user.name + " tham gia.");
                    updateStatus(2);
                })
                .leaving((user) => {
                    updateStatus(1);
                })
                .listen('.MoveMade', (e) => {
                    console.log("🔥 Nhận nước đi:", e);
                    game.move(e.move);
                    board.position(game.fen());

                    updateMoveHistory();
                    checkGameOver(); // Kiểm tra kết thúc sau khi đối thủ đi
                });
        };
    </script>
@endsection
