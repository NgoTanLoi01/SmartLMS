<?php

namespace App\Exports;

use App\Models\TeachingContract;
use App\Models\TeachingRecord;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalReportExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $report) {}

    public function array(): array
    {
        $rows = [];
        $summary = $this->report['summary'];

        $rows[] = ['BÁO CÁO GIẢNG DẠY & THANH TOÁN'];
        $rows[] = ['Ngày xuất', now()->format('d/m/Y H:i')];
        $rows[] = [];
        $rows[] = ['Chỉ số', 'Giá trị'];
        $rows[] = ['Số môn đã dạy', $summary['completed_subjects_count']];
        $rows[] = ['Tổng số môn', $summary['subjects_count']];
        $rows[] = ['Tổng số buổi', $summary['total_sessions']];
        $rows[] = ['Tổng tiền hợp đồng', $summary['total_contract_amount']];
        $rows[] = ['Đã nhận', $summary['received_amount']];
        $rows[] = ['Chưa nhận', $summary['remaining_amount']];

        $rows[] = [];
        $rows[] = ['Theo trung tâm'];
        $rows[] = $this->groupHeadings('Trung tâm');
        foreach ($this->report['byCenter'] as $row) {
            $rows[] = $this->groupValues($row);
        }

        $rows[] = [];
        $rows[] = ['Theo khóa'];
        $rows[] = $this->groupHeadings('Khóa');
        foreach ($this->report['byTerm'] as $row) {
            $rows[] = $this->groupValues($row);
        }

        $rows[] = [];
        $rows[] = ['Chi tiết giảng dạy'];
        $rows[] = ['Tên môn học', 'Lớp', 'Trung tâm', 'Khóa', 'Số buổi', 'Ngày bắt đầu', 'Ngày kết thúc', 'Trạng thái'];
        foreach ($this->report['teachingRecords'] as $record) {
            $rows[] = [
                $record->subject_name,
                $record->class_name,
                $record->center_name,
                $record->term_code,
                $record->planned_sessions,
                $record->start_date?->format('d/m/Y'),
                $record->end_date?->format('d/m/Y'),
                TeachingRecord::statuses()[$record->status] ?? $record->status,
            ];
        }

        $rows[] = [];
        $rows[] = ['Chi tiết thanh toán'];
        $rows[] = ['Số hợp đồng', 'Ngày ký', 'Tổng tiền', 'Đã nhận', 'Chưa nhận', 'Trạng thái', 'Ngày nhận', 'Minh chứng'];
        foreach ($this->report['contracts'] as $contract) {
            $rows[] = [
                $contract->contract_number,
                $contract->signed_date?->format('d/m/Y'),
                (float) $contract->total_amount,
                (float) $contract->received_amount,
                $contract->remaining_amount,
                TeachingContract::statuses()[$contract->status] ?? $contract->status,
                $contract->received_date?->format('d/m/Y'),
                $contract->evidence_url,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ([4, 13] as $row) {
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($this->headerStyle());
        }

        return [];
    }

    private function groupHeadings(string $label): array
    {
        return [$label, 'Số môn', 'Đã hoàn thành', 'Số buổi', 'Tổng tiền', 'Đã nhận', 'Chưa nhận'];
    }

    private function groupValues(array $row): array
    {
        return [
            $row['label'],
            $row['subjects_count'],
            $row['completed_subjects_count'],
            $row['total_sessions'],
            $row['total_contract_amount'],
            $row['received_amount'],
            $row['remaining_amount'],
        ];
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
        ];
    }
}
