@php
    $user = Auth::user();
    $role = $user->role;
    $isAdmin = $user->isAdmin();
    $isTeacher = $user->isTeacher();
    $isStudent = $role === 'student';

    $studentItemIsActive = fn (array $item): bool => request()->routeIs(...(array) ($item['patterns'] ?? $item['route']))
        && (empty($item['except']) || ! request()->routeIs(...(array) $item['except']));

    $studentPrimaryItems = [
        ['label' => 'Trang chủ', 'route' => 'dashboard', 'patterns' => ['dashboard'], 'icon' => 'fa-house', 'testid' => 'nav-dashboard'],
        ['label' => 'Khóa học của tôi', 'route' => 'courses.index', 'patterns' => ['courses.*'], 'except' => ['courses.materials.*'], 'icon' => 'fa-circle-play', 'testid' => 'nav-courses'],
        ['label' => 'Lịch học', 'route' => 'students.schedule', 'patterns' => ['students.schedule'], 'icon' => 'fa-calendar-days', 'testid' => 'nav-student-schedule'],
        ['label' => 'Bài tập', 'route' => 'assignments.index', 'patterns' => ['assignments.*'], 'icon' => 'fa-clipboard-check', 'testid' => 'nav-assignments'],
        ['label' => 'Kết quả học tập', 'route' => 'students.grades', 'patterns' => ['students.grades'], 'icon' => 'fa-chart-line', 'testid' => 'nav-student-grades'],
    ];

    $studentResourceItems = [
        ['label' => 'Kho học liệu', 'route' => 'materials.index', 'patterns' => ['materials.*', 'courses.materials.*'], 'icon' => 'fa-folder-open', 'testid' => 'nav-materials'],
        ['label' => 'Thông báo', 'route' => 'notifications.index', 'patterns' => ['notifications.*'], 'icon' => 'fa-bell', 'testid' => 'nav-notifications'],
    ];

    $learningItems = [
        ['label' => 'Quản lý khóa học', 'route' => 'courses.index', 'patterns' => ['courses.*'], 'except' => ['courses.materials.*'], 'icon' => 'fa-graduation-cap', 'testid' => 'nav-courses'],
        ['label' => 'Kho học liệu', 'route' => 'materials.index', 'patterns' => ['materials.*', 'courses.materials.*'], 'icon' => 'fa-folder-open'],
        ['label' => 'Bài tập', 'route' => 'assignments.index', 'patterns' => ['assignments.*'], 'icon' => 'fa-clipboard-check', 'testid' => 'nav-assignments'],
    ];

    $trainingItems = [
        ['label' => 'Chương trình học', 'route' => 'programs.index', 'patterns' => ['programs.*'], 'icon' => 'fa-sitemap'],
        ['label' => 'Lớp học', 'route' => 'classes.index', 'patterns' => ['classes.*'], 'icon' => 'fa-school', 'testid' => 'nav-classes'],
        ['label' => 'Lịch học', 'route' => 'schedules.index', 'patterns' => ['schedules.*'], 'icon' => 'fa-calendar-days'],
        ['label' => 'Giảng dạy', 'route' => 'teaching.index', 'patterns' => ['teaching.*'], 'icon' => 'fa-person-chalkboard'],
    ];

    $contentItems = [
        ['label' => 'Ngân hàng câu hỏi', 'route' => 'questions.index', 'patterns' => ['question-bank*', 'questions.*'], 'icon' => 'fa-layer-group'],
        ['label' => 'Tài liệu dùng chung', 'route' => 'shared-documents.index', 'patterns' => ['shared-documents.*'], 'icon' => 'fa-box-archive'],
        ['label' => 'Huấn luyện trợ lý AI', 'route' => 'documents.upload', 'patterns' => ['documents.*'], 'icon' => 'fa-wand-magic-sparkles'],
    ];

    $operationsItems = [
        ['label' => 'Tổng quan vận hành', 'route' => 'operations.dashboard', 'patterns' => ['operations.dashboard'], 'icon' => 'fa-chart-pie'],
        ['label' => 'Báo cáo vận hành', 'route' => 'reports.operations', 'patterns' => ['reports.operations'], 'icon' => 'fa-chart-column'],
        ['label' => 'Thanh toán giảng dạy', 'route' => 'payments.index', 'patterns' => ['payments.*'], 'icon' => 'fa-file-invoice-dollar'],
    ];

    $systemItems = [
        ['label' => 'Tài khoản người dùng', 'route' => 'users.index', 'patterns' => ['users.*'], 'icon' => 'fa-users-gear', 'testid' => 'nav-users'],
        ['label' => 'Tình trạng lưu trữ', 'route' => 'system.storage.index', 'patterns' => ['system.storage.*'], 'icon' => 'fa-cloud'],
        ['label' => 'Sao lưu dữ liệu', 'route' => 'system.backups.index', 'patterns' => ['system.backups.*'], 'icon' => 'fa-database'],
        ['label' => 'Nhật ký hệ thống', 'route' => 'audit-logs.index', 'patterns' => ['audit-logs.*'], 'icon' => 'fa-shield-halved'],
        ['label' => 'AI và hàng đợi', 'route' => 'system.ai-operations.index', 'patterns' => ['system.ai-operations.*'], 'icon' => 'fa-microchip'],
    ];

    $utilityItems = [
        ['label' => 'Tính điểm nghề', 'route' => 'tools.grade-calculator', 'patterns' => ['tools.grade-calculator'], 'icon' => 'fa-calculator'],
        ['label' => 'Cờ vua', 'route' => 'tools.chess.index', 'patterns' => ['tools.chess.*'], 'icon' => 'fa-chess'],
        ['label' => 'Cờ Caro', 'route' => 'tools.caro.index', 'patterns' => ['tools.caro.*'], 'icon' => 'fa-table-cells'],
    ];
@endphp

<aside class="sidebar" id="sidebar" aria-label="Điều hướng chính" data-testid="main-sidebar">

    @if ($isStudent)
        <nav class="sidebar-nav sidebar-nav--student" aria-label="Menu học viên" data-testid="nav-student-primary">
            <div class="student-nav-section student-nav-section--primary">
                @foreach ($studentPrimaryItems as $item)
                    @php $itemActive = $studentItemIsActive($item); @endphp
                    <a class="sidebar-item sidebar-item--student {{ $itemActive ? 'active' : '' }}"
                        href="{{ route($item['route']) }}" data-testid="{{ $item['testid'] }}"
                        @if ($itemActive) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <section class="student-nav-section" aria-labelledby="studentResourcesTitle">
                <h2 class="student-nav-section__title" id="studentResourcesTitle">Học tập của bạn</h2>
                @foreach ($studentResourceItems as $item)
                    @php $itemActive = $studentItemIsActive($item); @endphp
                    <a class="sidebar-item sidebar-item--student {{ $itemActive ? 'active' : '' }}"
                        href="{{ route($item['route']) }}" data-testid="{{ $item['testid'] }}"
                        @if ($itemActive) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </section>

            <section class="student-nav-section" aria-labelledby="studentUtilitiesTitle">
                <h2 class="student-nav-section__title" id="studentUtilitiesTitle">Tiện ích</h2>
                @foreach ($utilityItems as $item)
                    @php $itemActive = $studentItemIsActive($item); @endphp
                    <a class="sidebar-item sidebar-item--student {{ $itemActive ? 'active' : '' }}"
                        href="{{ route($item['route']) }}"
                        @if ($itemActive) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </section>
        </nav>
    @else
        <nav class="sidebar-nav">
            <a class="sidebar-item sidebar-item--primary {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}" data-testid="nav-dashboard"
                @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span>Trang tổng quan</span>
            </a>

            <x-navigation.group id="learningMenu" label="Học tập" icon="fa-book-open" :items="$learningItems"
                data-testid="nav-group-learning" />

            <x-navigation.group id="trainingMenu" label="Đào tạo" icon="fa-chalkboard-user" :items="$trainingItems"
                data-testid="nav-group-training" />
            <x-navigation.group id="contentMenu" label="Nội dung và AI" icon="fa-shapes" :items="$contentItems"
                data-testid="nav-group-content" />
            <x-navigation.group id="operationsMenu" label="Vận hành và báo cáo" icon="fa-chart-simple" :items="$operationsItems"
                data-testid="nav-group-operations" />
            @if ($isAdmin)
                <x-navigation.group id="systemMenu" label="Quản trị hệ thống" icon="fa-gears" :items="$systemItems"
                    data-testid="nav-group-system" />
            @endif

            <x-navigation.group id="utilityMenu" label="Tiện ích" icon="fa-toolbox" :items="$utilityItems"
                data-testid="nav-group-utilities" />
        </nav>

        <div class="sidebar-help">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Các nhóm đang dùng sẽ tự động mở để bạn luôn biết mình đang ở đâu.</span>
        </div>
    @endif
</aside>

<button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Đóng menu"></button>
