@extends('layouts.app')
@section('content')
    <div class="container py-5 text-center">
        <div class="card border-0 shadow-sm p-5 rounded-4 mx-auto" style="max-width: 500px;">
            <i class="fa-solid fa-chess-knight fa-4x text-primary mb-4"></i>
            <h2 class="fw-bold">Cờ Vua</h2>

            <div class="mt-4">
                <button onclick="createRoom()" class="btn btn-primary w-100 rounded-pill mb-3">Tạo phòng mới</button>

                <div class="hr-text text-muted my-3">Hoặc nhập mã phòng</div>

                <div class="input-group mb-3">
                    <input type="text" id="roomPin" class="form-control rounded-start-pill border-primary"
                        placeholder="Mã 6 số">
                    <button onclick="joinRoom()" class="btn btn-outline-primary rounded-end-pill px-4">Vào chơi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createRoom() {
            // Tạo mã pin 6 con số cho dễ nhớ
            const pin = Math.floor(100000 + Math.random() * 900000);
            window.location.href = `/tools/chess/${pin}`;
        }

        function joinRoom() {
            const pin = document.getElementById('roomPin').value;
            if (pin) {
                window.location.href = `/tools/chess/${pin}`;
            } else {
                alert('Vui lòng nhập mã phòng!');
            }
        }
    </script>
@endsection
