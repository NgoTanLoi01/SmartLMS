<?php

namespace Tests\Feature;

use App\Http\Controllers\OperationalReportController;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;
use Tests\TestCase;

class OperationalReportPrintExperienceTest extends TestCase
{
    public function test_print_report_embeds_the_existing_webp_logo(): void
    {
        $method = new ReflectionMethod(OperationalReportController::class, 'logoDataUri');
        $logoDataUri = $method->invoke(new OperationalReportController);

        $this->assertNotNull($logoDataUri);
        $this->assertStringStartsWith('data:image/webp;base64,', $logoDataUri);
        $this->assertNotFalse(base64_decode(substr($logoDataUri, strlen('data:image/webp;base64,')), true));
    }

    public function test_print_report_does_not_reserve_logo_space_when_logo_is_unavailable(): void
    {
        Auth::setUser(new User([
            'name' => 'System Admin',
            'role' => User::ROLE_ADMIN,
        ]));

        $html = view('reports.operations-print', [
            'filters' => [
                'teacher_id' => null,
                'center_name' => null,
                'term_code' => null,
                'month' => null,
                'year' => 2026,
            ],
            'summary' => [
                'completed_subjects_count' => 0,
                'subjects_count' => 0,
                'total_sessions' => 0,
                'total_contract_amount' => 0,
                'received_amount' => 0,
                'remaining_amount' => 0,
            ],
            'byCenter' => collect(),
            'byTerm' => collect(),
            'teachingRecords' => collect(),
            'contracts' => collect(),
            'teachers' => collect(),
            'periodLabel' => 'Năm 2026',
            'generatedAt' => Carbon::parse('2026-08-05 10:17:00'),
            'logoDataUri' => null,
        ])->render();

        $this->assertStringNotContainsString('<div class="brand-logo">', $html);
        $this->assertStringContainsString('display: table-header-group', $html);
        $this->assertStringContainsString('print-color-adjust: exact', $html);
    }
}
