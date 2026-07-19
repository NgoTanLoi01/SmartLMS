@section('title', 'SmartLMS - Đăng nhập hệ thống')
@section('meta_description',
    'Đăng nhập SmartLMS – Hệ thống quản lý học tập AI dành cho giáo viên và học viên. Quản lý
    khóa học, lớp học và kết quả học tập.')

    @extends('layouts.app')

@section('content')
    <style>
        /* Tổng thể tràn viền */
        .login-section {
            min-height: 100vh;
            display: flex;
            background-color: var(--sl-surface-muted);
            margin: 0;
        }

        /* Bên trái: Hình ảnh minh họa & Welcome */
        .login-illustration {
            flex: 1.2;
            background: var(--sl-primary-soft);
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
            background: var(--sl-surface);
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
            max-width: 100%;
            margin-bottom: 30px;
            width: auto;
        }

        .welcome-msg {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--sl-text);
            margin-bottom: 8px;
        }

        .sub-msg {
            color: var(--sl-text-muted);
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
            color: var(--sl-text-secondary);
        }

        .input-custom {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--sl-border);
            border-radius: var(--sl-radius-sm);
            transition: all 0.3s;
            background-color: #fcfcfd;
        }

        .input-custom:focus {
            outline: none;
            border-color: var(--sl-primary);
            background-color: var(--sl-surface);
            box-shadow: var(--sl-focus-ring);
        }

        .btn-submit-custom {
            width: 100%;
            padding: 14px;
            background-color: var(--sl-primary);
            color: white;
            border: none;
            border-radius: var(--sl-radius-sm);
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-submit-custom:hover {
            background-color: var(--sl-primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--sl-shadow-primary);
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--sl-text-muted);
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {
            .login-section {
                min-height: 100dvh;
            }

            .login-illustration {
                display: none;
            }

            .login-form-area {
                background-color: var(--sl-surface-muted);
                min-width: 0;
                padding: 24px 16px;
            }

            .login-card-custom {
                background: var(--sl-surface);
                min-width: 0;
                padding: 32px;
                border-radius: var(--sl-radius-lg);
                box-shadow: var(--sl-shadow-md);
            }
        }

        @media (max-width: 575px) {
            .login-form-area {
                align-items: flex-start;
                padding: 16px 12px;
            }

            .login-card-custom {
                padding: 26px 20px;
            }

            .login-card-custom .logo-brand {
                height: 58px;
                margin-bottom: 22px;
            }

            .welcome-msg {
                font-size: 1.55rem;
            }

            .sub-msg {
                margin-bottom: 28px;
            }
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
        }

        .footer-links a {
            text-decoration: none;
            color: inherit;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>

    <div class="login-section">
        <div class="login-illustration">
            <img src="{{ asset('auth-img1.webp') }}" alt="Minh họa học viên SmartLMS" width="574" height="349">
        </div>

        <div class="login-form-area">
            <div class="login-card-custom">
                <div class="text-center">
                    <img src="{{ asset('smartlms-logo-sharpened.webp') }}" class="logo-brand" alt="SmartLMS" width="800" height="200">
                    <h1 class="welcome-msg">Chào mừng! <x-ui.icon name="sparkles" class="text-primary" /></h1>
                    <p class="sub-msg">Vui lòng nhập thông tin để truy cập hệ thống.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0 small mb-4" style="border-radius: 12px;">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div class="form-group-custom">
                        <label for="loginInput">Tên đăng nhập</label>
                        <div class="position-relative">
                            <i class="fa-solid fa-user position-absolute top-50 translate-middle-y ms-3 text-muted"
                                aria-hidden="true"></i>
                            <input type="text" name="login" id="loginInput" class="input-custom ps-5"
                                placeholder="VD: nguyenvana" required value="{{ old('login') }}"
                                autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label for="passwordInput">Mật khẩu</label>
                        <div class="position-relative">
                            <i class="fa-solid fa-lock position-absolute top-50 translate-middle-y ms-3 text-muted"
                                aria-hidden="true"></i>
                            <input type="password" name="password" id="passwordInput" class="input-custom ps-5 pe-5"
                                placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" id="togglePassword"
                                aria-label="Hiện mật khẩu" aria-pressed="false"
                                class="btn position-absolute top-50 end-0 translate-middle-y me-2 text-muted border-0 shadow-none">
                                <i class="fa-solid fa-eye-slash" id="eyeIcon" aria-hidden="true"></i>
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
                        Đăng nhập ngay <i class="fa-solid fa-arrow-right ms-2"></i>
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
                            const isVisible = type === 'text';
                            togglePassword.setAttribute('aria-pressed', String(isVisible));
                            togglePassword.setAttribute('aria-label', isVisible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');

                            // Thay đổi icon con mắt
                            eyeIcon.classList.toggle('fa-eye-slash');
                            eyeIcon.classList.toggle('fa-eye');
                        });
                    });
                </script>

                <div class="footer-links">
                    &copy; {{ date('Y') }} SmartLMS v1.1.6<br>
                    Phát triển bởi
                    <a href="mailto:ngotanloi2424@gmail.com">
                        <strong>NgoTanLoi</strong>
                    </a>.
                </div>
            </div>
        </div>
    </div>
@endsection
