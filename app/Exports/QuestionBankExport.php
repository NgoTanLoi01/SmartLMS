<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionBankExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly Builder $questions) {}

    public function query(): Builder
    {
        return clone $this->questions;
    }

    public function headings(): array
    {
        return [
            'ID', 'Nội dung câu hỏi', 'Loại câu hỏi', 'Độ khó', 'Tags',
            'Ngân hàng', 'Khóa học', 'Đáp án / hướng dẫn chấm',
            'Cấu hình đáp án', 'Trạng thái', 'Phiên bản', 'Ngày cập nhật',
        ];
    }

    public function map($question): array
    {
        return [
            (int) $question->id,
            $this->safeText($question->question_text),
            $this->safeText($question->typeLabel()),
            $this->safeText(match ($question->difficulty) {
                'easy' => 'Dễ',
                'hard' => 'Khó',
                default => 'Trung bình',
            }),
            $this->safeText(collect($question->tags ?? [])->join(', ')),
            $this->safeText($question->questionBank?->name),
            $this->safeText($question->course?->title),
            $this->safeText($question->answerSummary()),
            $this->safeText($question->answer_config ? json_encode($question->answer_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null),
            $question->status === 'archived' ? 'Đã lưu trữ' : 'Đang sử dụng',
            (int) ($question->current_version ?: 1),
            $question->updated_at,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'L' => 'yyyy-mm-dd hh:mm:ss',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L1');
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
        $sheet->getStyle('H:I')->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('B')->setWidth(55);
        $sheet->getColumnDimension('H')->setWidth(38);
        $sheet->getColumnDimension('I')->setWidth(42);

        return [];
    }

    private function safeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = (string) $value;

        return preg_match('/^[=+\-@]/', ltrim($text)) === 1 ? "'{$text}" : $text;
    }
}
