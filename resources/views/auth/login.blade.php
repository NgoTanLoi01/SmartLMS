@extends('layouts.app')

@section('content')
    <style>
        /* Tổng thể tràn viền */
        .login-section {
            min-height: 100vh;
            display: flex;
            background-color: #f8fafc;
            margin: 0;
        }

        /* Bên trái: Hình ảnh minh họa & Welcome */
        .login-illustration {
            flex: 1.2;
            background: #ecf2fe;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px;
            color: white;
            position: relative;
        }

        .login-illustration img {
            max-width: 80%;
            height: auto;
            border-radius: 20px;
            /* box-shadow: 0 20px 50px rgba(0,0,0,0.2); */
        }

        .illustration-text {
            margin-top: 40px;
            text-align: center;
        }

        /* Bên phải: Form đăng nhập */
        .login-form-area {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-card-custom {
            width: 100%;
            max-width: 420px;
        }

        .login-card-custom .logo-brand {
            height: 70px;
            margin-bottom: 30px;
        }

        .welcome-msg {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .sub-msg {
            color: #64748b;
            margin-bottom: 35px;
        }

        /* Style Input giống ảnh mẫu */
        .form-group-custom {
            margin-bottom: 20px;
        }

        .form-group-custom label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
            color: #334155;
        }

        .input-custom {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s;
            background-color: #fcfcfd;
        }

        .input-custom:focus {
            outline: none;
            border-color: #1a3a5a;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(26, 58, 90, 0.05);
        }

        .btn-submit-custom {
            width: 100%;
            padding: 14px;
            background-color: #1a3a5a;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-submit-custom:hover {
            background-color: #122b44;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 58, 90, 0.15);
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {
            .login-illustration {
                display: none;
            }

            .login-form-area {
                background-color: #f8fafc;
            }

            .login-card-custom {
                background: white;
                padding: 35px;
                border-radius: 24px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            }
        }
    </style>

    <div class="login-section">
        <div class="login-illustration">
            <img src="auth-img1.png" alt="Education Illustration">
        </div>

        <div class="login-form-area">
            <div class="login-card-custom">
                <div class="text-center">
                    <img src="{{ asset('smartlms-logo-sharpened.png') }}" class="logo-brand" alt="Logo">
                    <h1 class="welcome-msg">Chào mừng! 👋</h1>
                    <p class="sub-msg">Vui lòng nhập thông tin để truy cập hệ thống.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0 small mb-4" style="border-radius: 12px;">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div class="form-group-custom">
                        <label>Địa chỉ Email</label>
                        <div class="position-relative">
                            <i class="fas fa-envelope position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                            <input type="email" name="email" class="input-custom ps-5" placeholder="hovaten@gmail.com"
                                required value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label>Mật khẩu</label>
                        <div class="position-relative">
                            <i class="fas fa-lock position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                            <input type="password" name="password" id="passwordInput" class="input-custom ps-5 pe-5"
                                placeholder="••••••••" required>
                            <button type="button" id="togglePassword"
                                class="btn position-absolute top-50 end-0 translate-middle-y me-2 text-muted border-0 shadow-none">
                                <i class="fas fa-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label small text-muted" for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-custom">
                        Đăng nhập ngay <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const togglePassword = document.querySelector('#togglePassword');
                        const password = document.querySelector('#passwordInput');
                        const eyeIcon = document.querySelector('#eyeIcon');

                        togglePassword.addEventListener('click', function() {
                            // Chuyển đổi kiểu input từ password sang text và ngược lại
                            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                            password.setAttribute('type', type);

                            // Thay đổi icon con mắt
                            eyeIcon.classList.toggle('fa-eye-slash');
                            eyeIcon.classList.toggle('fa-eye');
                        });
                    });
                </script>

                <div class="footer-links">
                    &copy; {{ date('Y') }} SmartLMS v1.1.1<br>
                    Phát triển bởi <strong>NgoTanLoi</strong>.
                </div>
            </div>
        </div>
    </div>
@endsection
