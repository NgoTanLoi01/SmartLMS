<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnWidths
{
    protected $course, $students, $columns, $attendanceData;
    protected int $rowNumber = 0;

    public function __construct($course, $students, $columns, $attendanceData)
    {
        $this->course = $course;
        $this->students = $students;
        $this->columns = $columns;
        $this->attendanceData = $attendanceData;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        $headers = ['STT', 'Họ và Tên'];
        foreach ($this->columns as $col) {
            $headers[] = $col->name;
        }

        return [['TRƯỜNG TRUNG CẤP ÂU VIỆT'], ['BẢNG ĐIỂM DANH & ĐIỂM SỐ CHI TIẾT'], ['Môn học: ' . $this->course->title], ['Ngày xuất: ' . date('d/m/Y H:i')], ['Quy ước: trống = Có mặt  |  V = Vắng  |  M = Đi muộn  |  P = Có phép'], $headers];
    }

    public function map($student): array
    {
        $row = [++$this->rowNumber, $student->name];

        foreach ($this->columns as $col) {
            $value = $this->attendanceData[$student->id][$col->id] ?? null;
            $row[] = $this->formatExportValue($col->type, $value);
        }

        return $row;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 7, 'B' => 30];

        foreach ($this->columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 3);
            $widths[$letter] = match ($column->type) {
                'attendance' => 13,
                'grade' => 11,
                default => 24,
            };
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $totalCols = count($this->columns) + 2; // STT + Name
        $lastColumn = Coordinate::stringFromColumnIndex($totalCols);

        // Merge tiêu đề
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->mergeCells("A5:{$lastColumn}5");

        return [
            // Tiêu đề
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // Header bảng
            6 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalCols = count($this->columns) + 2;
                $lastColumn = Coordinate::stringFromColumnIndex($totalCols);
                $lastRow = count($this->students) + 6;

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(.4)->setBottom(.4)->setLeft(.3)->setRight(.3);
                $sheet->setAutoFilter("A6:{$lastColumn}6");
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(23);
                $sheet->getRowDimension(6)->setRowHeight(34);

                // 📌 Freeze header
                $sheet->freezePane('A7');

                // 🧱 Border toàn bảng
                $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A7:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("C7:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($row = 7; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(25);
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                    }
                }

                foreach ($this->columns as $index => $column) {
                    $letter = Coordinate::stringFromColumnIndex($index + 3);
                    $headerColor = match ($column->type) {
                        'attendance' => '1D4ED8',
                        'grade' => 'D97706',
                        default => '64748B',
                    };
                    $sheet->getStyle("{$letter}6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerColor);
                }

                // 🎯 Tô màu theo dữ liệu
                foreach ($this->students as $index => $student) {
                    $row = $index + 7;

                    foreach ($this->columns as $i => $col) {
                        $colLetter = Coordinate::stringFromColumnIndex($i + 3);

                        if ($col->type !== 'attendance') continue;
                        $status = $this->normalizeAttendanceStatus($this->attendanceData[$student->id][$col->id] ?? null);

                        if ($status === 'absent') {
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FEE2E2'],
                                ],
                            ]);
                        } elseif ($status === 'present') {
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F0FDF4'],
                                ],
                            ]);
                        } elseif ($status === 'late') {
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                            ]);
                        } elseif ($status === 'excused') {
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                            ]);
                        }
                    }
                }

                // 📐 Căn giữa STT
                $sheet
                    ->getStyle("A7:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    private function formatExportValue(string $type, $value): string
    {
        if ($type !== 'attendance') return (string) ($value ?? '');

        return match ($this->normalizeAttendanceStatus($value)) {
            'absent' => 'V',
            'late' => 'M',
            'excused' => 'P',
            default => '',
        };
    }

    private function normalizeAttendanceStatus($value): string
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['absent', 'v', 'vắng', 'vang', 'nghỉ', 'nghi', '0', 'no', 'false'], true)) return 'absent';
        if (in_array($normalized, ['late', 'm', 'muộn', 'muon', 'đi muộn', 'di muon'], true)) return 'late';
        if (in_array($normalized, ['excused', 'p', 'phép', 'phep', 'có phép', 'co phep'], true)) return 'excused';

        return 'present';
    }
}
