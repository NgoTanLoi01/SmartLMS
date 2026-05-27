@extends('layouts.app')
<style>
    .highlight-square {
        position: relative;
    }

    /* Ô nước đi thường */
    .highlight-square::after {
        content: '';

        position: absolute;

        width: 18px;
        height: 18px;

        background: rgba(40, 167, 69, 0.55);

        border-radius: 50%;

        top: 50%;
        left: 50%;

        transform: translate(-50%, -50%);

        pointer-events: none;
    }

    /* Ô có thể ăn quân */
    .highlight-capture::after {

        content: '';

        position: absolute;

        width: calc(100% - 10px);
        height: calc(100% - 10px);

        border: 5px solid rgba(220, 53, 69, 0.8);

        border-radius: 50%;

        top: 50%;
        left: 50%;

        transform: translate(-50%, -50%);

        pointer-events: none;
    }

    /* Ô đang chọn */
    .selected-square {
        box-shadow: inset 0 0 3px 3px rgba(255, 193, 7, 0.9);
    }
</style>
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

                        <!-- Nút thoát khi đang chơi -->
                        <a href="{{ route('tools.chess.index') }}" id="normal-exit-btn"
                            class="btn btn-light rounded-pill px-4">
                            Thoát phòng
                        </a>

                        <!-- Nút thoát sau khi kết thúc -->
                        <button id="finish-exit-btn" class="btn btn-danger rounded-pill px-4 d-none">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Thoát trận
                        </button>

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

            // =========================
            // AXIOS + CSRF
            // =========================
            window.axios = axios;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
            }

            // =========================
            // GAME CONFIG
            // =========================
            var game = new Chess();
            var roomId = "{{ $roomId }}";
            var currentUserId = {{ auth()->id() ?? 'null' }};
            var myColor = 'white';
            let isGameFinished = false;
            var confirmedPlayers = []; // Danh sách 2 người chơi thật

            // =========================
            // HIGHLIGHT LEGAL MOVES
            // =========================
            function removeHighlights() {
                $('#myBoard .square-55d63').removeClass(
                    'highlight-square highlight-capture selected-square'
                );
            }

            function highlightLegalMoves(square) {
                removeHighlights();

                const moves = game.moves({
                    square: square,
                    verbose: true
                });
                if (moves.length === 0) return;

                $(`#myBoard .square-${square}`).addClass('selected-square');

                moves.forEach(move => {
                    const targetEl = $(`#myBoard .square-${move.to}`);
                    if (move.flags.includes('c') || move.flags.includes('e')) {
                        targetEl.addClass('highlight-capture');
                    } else {
                        targetEl.addClass('highlight-square');
                    }
                });
            }

            // =========================
            // REVERB + ECHO
            // =========================
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ env('REVERB_APP_KEY') }}',
                wsHost: 'ws.smartlms.io.vn',
                wsPort: 443,
                wssPort: 443,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
            });

            // =========================
            // STATUS UI
            // =========================
            function updateStatus(count) {
                const badge = document.getElementById('status-badge');
                if (!badge) return;

                if (count >= 2) {
                    badge.className = "badge bg-success rounded-pill px-3 py-2";
                    badge.innerHTML = "Trận đấu bắt đầu!";
                } else {
                    badge.className = "badge bg-secondary rounded-pill px-3 py-2";
                    badge.innerHTML = "Đang đợi đối thủ...";
                }
            }

            // =========================
            // MOVE HISTORY
            // =========================
            function updateMoveHistory() {
                const history = game.history();
                const tbody = document.getElementById('move-history-body');
                if (!tbody) return;

                tbody.innerHTML = '';

                for (let i = 0; i < history.length; i += 2) {
                    const moveNumber = (i / 2) + 1;
                    const whiteMove = history[i] || '';
                    const blackMove = history[i + 1] || '';

                    tbody.innerHTML += `
                    <tr>
                        <td class="text-muted">${moveNumber}</td>
                        <td class="fw-bold text-primary">${whiteMove}</td>
                        <td class="fw-bold text-danger">${blackMove}</td>
                    </tr>
                `;
                }

                const tableContainer = tbody.parentElement.parentElement;
                tableContainer.scrollTop = tableContainer.scrollHeight;
            }

            // =========================
            // GAME OVER
            // =========================
            function checkGameOver() {
                if (!game.game_over()) return;

                isGameFinished = true;

                let title = 'Trò chơi kết thúc!';
                let text = '';
                let icon = 'info';

                if (game.in_checkmate()) {
                    let winner = game.turn() === 'w' ? 'Quân Đen' : 'Quân Trắng';
                    title = 'Chiếu Bí!';
                    text = `${winner} đã giành chiến thắng! 🏆`;
                    icon = 'success';
                } else if (game.in_draw()) {
                    title = 'Hòa Cờ!';
                    text = 'Hai bên bất phân thắng bại 🤝';
                    icon = 'info';
                } else if (game.in_stalemate()) {
                    title = 'Hết Nước Đi!';
                    text = 'Ván cờ kết thúc hòa 🤝';
                    icon = 'warning';
                } else if (game.in_threefold_repetition()) {
                    title = 'Hòa Cờ!';
                    text = 'Lặp lại nước đi 3 lần 🔄';
                    icon = 'info';
                } else if (game.insufficient_material()) {
                    title = 'Không Đủ Quân!';
                    text = 'Không đủ quân để chiếu bí 🤝';
                    icon = 'info';
                }

                board.draggable = false;
                removeHighlights();

                // Xóa cache phòng khi ván cờ kết thúc
                window.axios.post(`/tools/chess/${roomId}/finish`)
                    .catch(error => console.error("Lỗi xóa cache phòng:", error));

                // Ẩn nút thoát thường
                const normalExitBtn = document.getElementById('normal-exit-btn');
                if (normalExitBtn) normalExitBtn.classList.add('d-none');

                // Hiện nút thoát trận
                const finishExitBtn = document.getElementById('finish-exit-btn');
                if (finishExitBtn) {
                    finishExitBtn.classList.remove('d-none');
                    finishExitBtn.onclick = function() {
                        Swal.fire({
                            title: 'Thoát trận?',
                            text: 'Bạn sẽ rời khỏi bàn cờ hiện tại.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Thoát',
                            cancelButtonText: 'Ở lại',
                            confirmButtonColor: '#dc3545'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "/tools/chess";
                            }
                        });
                    };
                }

                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: 'Xem lại bàn cờ',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: true,
                    allowEscapeKey: true
                });
            }

            // =========================
            // CHESSBOARD
            // =========================
            const boardEl = document.getElementById('myBoard');

            if (boardEl) {
                var board = Chessboard('myBoard', {
                    draggable: true,
                    position: 'start',
                    pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',

                    // =========================
                    // HOVER HIGHLIGHT
                    // =========================
                    onMouseoverSquare: function(square, piece) {
                        if (isGameFinished) return;
                        if (!piece) return;

                        if (
                            (myColor === 'white' && piece.startsWith('w')) ||
                            (myColor === 'black' && piece.startsWith('b'))
                        ) {
                            if (
                                (game.turn() === 'w' && myColor === 'white') ||
                                (game.turn() === 'b' && myColor === 'black')
                            ) {
                                highlightLegalMoves(square);
                            }
                        }
                    },

                    // =========================
                    // REMOVE HIGHLIGHT
                    // =========================
                    onMouseoutSquare: function() {
                        removeHighlights();
                    },

                    // =========================
                    // DRAG START
                    // =========================
                    onDragStart: function(source, piece) {
                        if (isGameFinished || game.game_over()) return false;

                        if (
                            (myColor === 'white' && piece.charAt(0) !== 'w') ||
                            (myColor === 'black' && piece.charAt(0) !== 'b')
                        ) return false;

                        if (
                            (game.turn() === 'w' && myColor !== 'white') ||
                            (game.turn() === 'b' && myColor !== 'black')
                        ) return false;
                    },

                    // =========================
                    // DROP
                    // =========================
                    onDrop: function(source, target) {
                        removeHighlights();

                        if (isGameFinished) return 'snapback';

                        var move = game.move({
                            from: source,
                            to: target,
                            promotion: 'q'
                        });

                        if (move === null) return 'snapback';

                        updateMoveHistory();
                        checkGameOver();

                        window.axios.post(`/tools/chess/${roomId}/move`, {
                                move: move
                            })
                            .catch(error => {
                                console.error("Lỗi gửi nước đi:", error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi kết nối',
                                    text: 'Không thể gửi nước đi.'
                                });
                            });
                    },

                    // =========================
                    // SNAP END
                    // =========================
                    onSnapEnd: function() {
                        board.position(game.fen());
                    }
                });

                setTimeout(() => {
                    board.resize();
                }, 500);
            }

            // =========================
            // REALTIME ROOM
            // =========================
            window.Echo.join(`chess.${roomId}`)

                // =========================
                // HERE
                // =========================
                .here((users) => {
                    console.log("👥 Người chơi:", users);

                    // Lưu danh sách player thật
                    confirmedPlayers = users.map(u => u.id);

                    const otherUsers = users.filter(u => u.id !== currentUserId);

                    if (otherUsers.length > 0) {
                        myColor = 'black';
                        board.orientation('black');
                        console.log("⚫ Bạn là ĐEN");
                    } else {
                        myColor = 'white';
                        board.orientation('white');
                        console.log("⚪ Bạn là TRẮNG");
                    }

                    updateStatus(users.length);
                })

                // =========================
                // JOINING
                // =========================
                .joining((user) => {
                    console.log(user.name + " tham gia");

                    // Chỉ thêm vào confirmedPlayers nếu chưa đủ 2
                    if (confirmedPlayers.length < 2 && !confirmedPlayers.includes(user.id)) {
                        confirmedPlayers.push(user.id);
                    }

                    updateStatus(2);

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `${user.name} đã tham gia`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                })

                // =========================
                // LEAVING
                // =========================
                .leaving((user) => {
                    console.log(user.name + " rời phòng");

                    // ✅ Bỏ qua nếu người thoát không phải player thật
                    if (!confirmedPlayers.includes(user.id)) {
                        console.log("🚫 Người ngoài thoát, bỏ qua.");
                        return;
                    }

                    updateStatus(1);

                    if (!isGameFinished) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Đối thủ đã thoát',
                            text: 'Trận đấu đã kết thúc',
                            confirmButtonText: 'Thoát trận',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = "/tools/chess";
                        });
                    }
                })

                // =========================
                // RECEIVE MOVE
                // =========================
                .listen('.MoveMade', (e) => {
                    console.log("🔥 Nhận nước đi:", e);

                    removeHighlights();
                    if (isGameFinished) return;

                    game.move(e.move);
                    board.position(game.fen());
                    updateMoveHistory();
                    checkGameOver();
                })

                // =========================
                // ERROR – PHÒNG ĐÃ ĐẦY
                // =========================
                .error((error) => {
                    console.error("❌ Lỗi kết nối phòng:", error);

                    if (error.status === 403) {
                        Swal.fire({
                            icon: 'error',
                            title: '🔒 Phòng đã đầy!',
                            text: 'Phòng này đã có đủ 2 người chơi. Bạn không thể vào.',
                            confirmButtonText: 'Quay lại',
                            confirmButtonColor: '#dc3545',
                            allowOutsideClick: false,
                        }).then(() => {
                            window.location.href = '/tools/chess';
                        });
                    }
                });
        };
    </script>
@endsection
