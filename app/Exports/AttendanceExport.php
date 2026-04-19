<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, ShouldAutoSize
{
    protected $course, $students, $columns, $attendanceData;

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

        return [['TRƯỜNG TRUNG CẤP ÂU VIỆT'], ['BẢNG ĐIỂM DANH & ĐIỂM SỐ CHI TIẾT'], ['Môn học: ' . $this->course->title], ['Ngày xuất: ' . date('d/m/Y H:i')], [], $headers];
    }

    public function map($student): array
    {
        static $stt = 1;

        $row = [$stt++, $student->name];

        foreach ($this->columns as $col) {
            $row[] = $this->attendanceData[$student->id][$col->id] ?? '-';
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $totalCols = count($this->columns) + 2; // STT + Name
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        // Merge tiêu đề
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");

        return [
            // Tiêu đề
            1 => [
                'font' => ['bold' => true, 'size' => 16],
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

            // Header bảng
            6 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '007BFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
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
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
                $lastRow = count($this->students) + 6;

                // 📌 Freeze header
                $sheet->freezePane('A7');

                // 🧱 Border toàn bảng
                $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // 🎯 Tô màu theo dữ liệu
                foreach ($this->students as $index => $student) {
                    $row = $index + 7;

                    foreach ($this->columns as $i => $col) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 3);

                        $value = $this->attendanceData[$student->id][$col->id] ?? '-';

                        if ($value === 'V') {
                            // 🔴 Vắng
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'font' => ['color' => ['rgb' => 'FFFFFF']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'DC3545'],
                                ],
                            ]);
                        } elseif ($value === 'P') {
                            // 🟢 Có mặt
                            $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => '28A745'],
                                ],
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
}
