<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\SmartNotification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonalAssistantService
{
    private const RESULT_LIMIT = 8;

    public function answer(string $message, User $user): ?string
    {
        $intent = $this->normalize($message);

        if ($this->containsAny($intent, ['hom nay co gi', 'tong quan hom nay', 'viec hom nay', 'toi can lam gi hom nay'])) {
            return $this->dailyOverview($user);
        }

        if ($this->isScheduleIntent($intent)) {
            return $this->scheduleAnswer($intent, $user);
        }

        if ($this->isTaskIntent($intent, $user)) {
            return $this->taskAnswer($user);
        }

        if ($this->containsAny($intent, ['thong bao', 'tin moi', 'nhac nho'])) {
            return $this->notificationAnswer($user);
        }

        if ($this->containsAny($intent, ['toi la ai', 'tai khoan cua toi', 'thong tin cua toi'])) {
            return sprintf(
                'Bạn đang đăng nhập với tên **%s**, vai trò **%s**.',
                $user->name,
                $this->roleLabel($user)
            );
        }

        return null;
    }

    private function dailyOverview(User $user): string
    {
        if ($user->isAdmin()) {
            return $this->notificationAnswer($user);
        }

        return implode("\n\n", [
            $this->scheduleAnswer('lich hom nay', $user),
            $this->taskAnswer($user),
            $this->notificationAnswer($user, 3),
        ]);
    }

    private function scheduleAnswer(string $intent, User $user): string
    {
        if (! $user->isStudent() && ! $user->isTeacher()) {
            return 'Lịch cá nhân hiện áp dụng cho tài khoản học sinh và giáo viên.';
        }

        $now = Carbon::now(config('app.timezone'));
        $query = $this->scheduleQuery($user);
        $periodLabel = 'sắp tới';
        $limit = self::RESULT_LIMIT;

        if ($this->containsAny($intent, ['tiep theo', 'ke tiep', 'gan nhat', 'sap toi'])) {
            $periodLabel = 'tiếp theo';
            $limit = 1;
            $query->where(function ($builder) use ($now) {
                $builder->whereDate('schedules.schedule_date', '>', $now->toDateString())
                    ->orWhere(function ($sameDay) use ($now) {
                        $sameDay->whereDate('schedules.schedule_date', $now->toDateString())
                            ->where('schedules.start_time', '>=', $now->format('H:i:s'));
                    });
            });
        } elseif (str_contains($intent, 'ngay mai')) {
            $date = $now->copy()->addDay();
            $periodLabel = 'ngày mai ('.$date->format('d/m/Y').')';
            $query->whereDate('schedules.schedule_date', $date->toDateString());
        } elseif (str_contains($intent, 'tuan nay')) {
            $periodLabel = 'từ hôm nay đến cuối tuần';
            $query->whereDate('schedules.schedule_date', '>=', $now->toDateString())
                ->whereDate('schedules.schedule_date', '<=', $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString());
        } elseif (str_contains($intent, 'hom nay')) {
            $periodLabel = 'hôm nay ('.$now->format('d/m/Y').')';
            $query->whereDate('schedules.schedule_date', $now->toDateString());
        } else {
            $query->whereDate('schedules.schedule_date', '>=', $now->toDateString());
        }

        $schedules = $query
            ->orderBy('schedules.schedule_date')
            ->orderBy('schedules.start_time')
            ->limit($limit)
            ->get();

        $activity = $user->isTeacher() ? 'buổi dạy' : 'buổi học';
        if ($schedules->isEmpty()) {
            return "Bạn không có {$activity} nào {$periodLabel}.";
        }

        $lines = $schedules->values()->map(function ($schedule, int $index) {
            $date = Carbon::parse($schedule->schedule_date)->format('d/m/Y');
            $time = substr((string) $schedule->start_time, 0, 5).'-'.substr((string) $schedule->end_time, 0, 5);
            $details = ["**{$schedule->course_title}**", $schedule->class_name];

            if (filled($schedule->room)) {
                $details[] = 'Phòng '.$schedule->room;
            }

            if (filled($schedule->note)) {
                $details[] = 'Ghi chú: '.$schedule->note;
            }

            return ($index + 1).". {$date} · {$time} · ".implode(' · ', $details);
        });

        return "Bạn có {$schedules->count()} {$activity} {$periodLabel}:\n".$lines->implode("\n");
    }

    private function scheduleQuery(User $user)
    {
        $query = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.id')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('schedules.status', Schedule::STATUS_ACTIVE)
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->where('courses.status', '!=', Course::STATUS_ARCHIVED)
            ->select(
                'schedules.schedule_date',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.room',
                'schedules.note',
                'courses.title as course_title',
                'classes.name as class_name'
            );

        if ($user->isTeacher()) {
            return $query->where('classes.teacher_id', $user->id);
        }

        return $query
            ->where('courses.status', Course::STATUS_PUBLISHED)
            ->where(function ($visibility) {
                $visibility->whereNull('courses.available_from')
                    ->orWhere('courses.available_from', '<=', now());
            })
            ->whereExists(function ($membership) use ($user) {
                $membership->selectRaw('1')
                    ->from('class_user')
                    ->whereColumn('class_user.class_id', 'schedules.class_id')
                    ->where('class_user.user_id', $user->id);
            })
            ->whereExists(function ($courseClass) {
                $courseClass->selectRaw('1')
                    ->from('class_course')
                    ->whereColumn('class_course.class_id', 'schedules.class_id')
                    ->whereColumn('class_course.course_id', 'schedules.course_id');
            });
    }

    private function taskAnswer(User $user): string
    {
        if ($user->isStudent()) {
            return $this->studentTaskAnswer($user);
        }

        if ($user->isTeacher()) {
            return $this->teacherTaskAnswer($user);
        }

        return 'Danh sách việc cá nhân hiện áp dụng cho tài khoản học sinh và giáo viên.';
    }

    private function studentTaskAnswer(User $user): string
    {
        $assignments = Assignments::query()
            ->with('course:id,title')
            ->visibleToStudents()
            ->notArchived()
            ->where('due_date', '>=', now())
            ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('course', function ($course) use ($user) {
                $course->visibleToStudents()
                    ->whereHas('classes', function ($classroom) use ($user) {
                        $classroom->where('classes.status', Classroom::STATUS_ACTIVE)
                            ->whereHas('students', fn ($student) => $student->whereKey($user->id));
                    });
            })
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($assignments->isEmpty()) {
            return 'Bạn không có bài tập chưa nộp nào đang đến hạn.';
        }

        $lines = $assignments->values()->map(fn (Assignments $assignment, int $index) => sprintf(
            '%d. **%s** · %s · hạn %s',
            $index + 1,
            $assignment->title,
            $assignment->course?->title ?? 'Khóa học',
            $assignment->due_date->timezone(config('app.timezone'))->format('H:i d/m/Y')
        ));

        return "Bạn có {$assignments->count()} bài tập chưa nộp sắp đến hạn:\n".$lines->implode("\n");
    }

    private function teacherTaskAnswer(User $user): string
    {
        $assignments = Assignments::query()
            ->with('course:id,title')
            ->withCount(['submissions as pending_grading_count' => fn ($query) => $query->whereNull('grade')])
            ->notArchived()
            ->whereHas('course', fn ($course) => $course->where('teacher_id', $user->id)->notArchived())
            ->whereHas('submissions', fn ($submission) => $submission->whereNull('grade'))
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($assignments->isEmpty()) {
            return 'Bạn không có bài nộp nào đang chờ chấm.';
        }

        $lines = $assignments->values()->map(fn (Assignments $assignment, int $index) => sprintf(
            '%d. **%s** · %s · %d bài chờ chấm',
            $index + 1,
            $assignment->title,
            $assignment->course?->title ?? 'Khóa học',
            $assignment->pending_grading_count
        ));

        return "Các bài tập đang cần bạn xử lý:\n".$lines->implode("\n");
    }

    private function notificationAnswer(User $user, int $limit = 5): string
    {
        $notifications = SmartNotification::query()
            ->forUser($user->id)
            ->unread()
            ->latest()
            ->limit($limit)
            ->get(['title', 'message']);

        if ($notifications->isEmpty()) {
            return 'Bạn không có thông báo chưa đọc.';
        }

        $lines = $notifications->values()->map(fn (SmartNotification $notification, int $index) => sprintf(
            '%d. **%s** — %s',
            $index + 1,
            $notification->title,
            $notification->message
        ));

        return "Bạn có {$notifications->count()} thông báo chưa đọc gần nhất:\n".$lines->implode("\n");
    }

    private function isScheduleIntent(string $intent): bool
    {
        if ($this->containsAny($intent, [
            'lich hoc', 'lich day', 'lich hom nay', 'lich ngay mai', 'thoi khoa bieu',
            'hom nay hoc', 'hom nay day', 'ngay mai hoc', 'ngay mai day',
            'tiet hoc', 'tiet day', 'buoi hoc', 'buoi day',
        ])) {
            return true;
        }

        $hasTimeReference = $this->containsAny($intent, ['hom nay', 'ngay mai', 'tuan nay', 'tiep theo', 'ke tiep']);
        $hasPersonalActivity = preg_match('/\b(toi|minh|em)\s+(co\s+)?(hoc|day)\b/', $intent) === 1
            || $this->containsAny($intent, ['hoc lop', 'day lop']);
        $hasScheduleWord = preg_match('/\blich\b/', $intent) === 1;

        return ($hasTimeReference && ($hasPersonalActivity || $hasScheduleWord))
            || $this->containsAny($intent, ['lich cua toi', 'xem lich']);
    }

    private function isTaskIntent(string $intent, User $user): bool
    {
        if ($user->isStudent()) {
            return $this->containsAny($intent, [
                'bai tap cua toi', 'bai tap nao', 'con bai tap', 'bai tap sap', 'han nop', 'chua nop', 'can nop',
            ]);
        }

        if ($user->isTeacher()) {
            return $this->containsAny($intent, [
                'chua cham', 'can cham', 'cho toi cham', 'bai cho cham', 'bai nop chua cham', 'bai nao dang cho',
            ]);
        }

        return false;
    }

    private function roleLabel(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => 'Quản trị viên',
            User::ROLE_TEACHER => 'Giáo viên',
            User::ROLE_STUDENT => 'Học sinh',
            default => 'Người dùng',
        };
    }

    private function normalize(string $message): string
    {
        return (string) Str::of($message)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish();
    }

    private function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
