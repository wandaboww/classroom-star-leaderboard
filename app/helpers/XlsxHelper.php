<?php
/**
 * XlsxHelper — Wrapper untuk PhpSpreadsheet
 * Import & Export file .xlsx dengan layout rapi dan format profesional
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class XlsxHelper
{
    /**
     * Baca file Excel (.xlsx) yang diupload, mendeteksi header secara dinamis
     * Mengembalikan array of rows: ['_row_num' => 5, 'nis' => '...', 'name' => '...', 'class' => '...']
     */
    public static function readImportFile(string $filePath): array
    {
        $reader = new XlsxReader();
        $reader->setReadDataOnly(false); // Baca format agar nilai teks/string terbaca utuh
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows))
            return [];

        // Cari baris header (mencari baris yang memuat kata kunci NIS/NAMA/KELAS)
        $headerRowIndex = 1;
        $headerMap = [];

        foreach ($rows as $rowIndex => $cols) {
            $normalized = [];
            foreach ($cols as $colKey => $val) {
                $normalized[$colKey] = strtolower(trim((string) $val));
            }

            // Cek apakah baris ini memuat NIS atau NAMA
            $hasNis = false;
            $hasName = false;
            foreach ($normalized as $colKey => $headerText) {
                if (in_array($headerText, ['nis', 'nis *', 'nomor induk', 'nomor induk siswa', 'no induk'])) {
                    $hasNis = true;
                    $headerMap['nis'] = $colKey;
                }
                if (in_array($headerText, ['nama', 'nama siswa', 'nama lengkap', 'nama lengkap siswa', 'nama siswa *', 'nama lengkap *', 'full name', 'name'])) {
                    $hasName = true;
                    $headerMap['name'] = $colKey;
                }
                if (in_array($headerText, ['kelas', 'kelas *', 'class', 'class name', 'nama kelas'])) {
                    $headerMap['class'] = $colKey;
                }
            }

            if ($hasNis || $hasName) {
                $headerRowIndex = $rowIndex;
                break;
            }
        }

        // Jika tidak terdeteksi via nama, fallback ke kolom standar: A=No, B=NIS, C=Nama, D=Kelas
        if (empty($headerMap['nis']))
            $headerMap['nis'] = 'B';
        if (empty($headerMap['name']))
            $headerMap['name'] = 'C';
        if (empty($headerMap['class']))
            $headerMap['class'] = 'D';

        $result = [];
        $maxRow = count($rows);

        for ($i = $headerRowIndex + 1; $i <= $maxRow; $i++) {
            if (!isset($rows[$i]))
                continue;
            $row = $rows[$i];

            $nis = trim((string) ($row[$headerMap['nis']] ?? ''));
            $name = trim((string) ($row[$headerMap['name']] ?? ''));
            $class = trim((string) ($row[$headerMap['class']] ?? ''));

            // Lewati baris kosong
            if ($nis === '' && $name === '' && $class === '') {
                continue;
            }

            $result[] = [
                '_row_num' => $i,
                'nis' => $nis,
                'name' => $name,
                'class' => $class,
            ];
        }

        return $result;
    }

    /**
     * Generate template XLSX kosong dengan format yang rapi dan profesional
     * Kolom: NO, NIS, NAMA SISWA, KELAS
     */
    public static function generateImportTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data Siswa');

        // Pastikan gridlines terlihat saat dibuka di Excel
        $sheet->setShowGridLines(true);

        // 1. Judul Banner
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA SISWA — CLASSROOM STAR');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'size' => 13,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10122A'], // Dark navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Petunjuk / Instruksi
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Petunjuk: Kolom bertanda (*) wajib diisi. Masukkan nama kelas sesuai kelas di sistem (contoh: XI IPA 1).');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'size' => 9,
                'italic' => true,
                'color' => ['rgb' => '333333'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF8E1'], // Soft gold pastel
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'indent' => 1,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Baris kosong pemisah
        $sheet->getRowDimension(3)->setRowHeight(8);

        // 3. Header Tabel (Baris 4)
        $headers = [
            'A' => ['label' => 'NO', 'align' => Alignment::HORIZONTAL_CENTER],
            'B' => ['label' => 'NIS *', 'align' => Alignment::HORIZONTAL_CENTER],
            'C' => ['label' => 'NAMA LENGKAP SISWA *', 'align' => Alignment::HORIZONTAL_LEFT],
            'D' => ['label' => 'KELAS *', 'align' => Alignment::HORIZONTAL_CENTER],
        ];

        foreach ($headers as $col => $cfg) {
            $sheet->setCellValue($col . '4', $cfg['label']);
        }

        $headerStyle = [
            'font' => [
                'name' => 'Segoe UI',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E2240'], // Primary brand card color
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '444466'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A4:D4')->applyFromArray($headerStyle);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // 4. Sample Data Rows (Baris 5 - 10)
        $samples = [
            [1, '1001', 'Citra Lestari', 'XI IPA 1'],
            [2, '1002', 'Andi Pratama', 'XI IPA 1'],
            [3, '1003', 'Farhan Nugraha', 'XI IPA 1'],
            [4, '1004', 'Dewi Anggraini', 'XI IPA 1'],
            [5, '1005', 'Alya Putri', 'XI IPA 2'],
            [6, '1006', 'Dina Marlina', 'XI IPS 1'],
        ];

        $dataStyle = [
            'font' => [
                'name' => 'Segoe UI',
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D4E4'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        foreach ($samples as $i => $row) {
            $r = $i + 5;
            $sheet->setCellValue("A$r", $row[0]);
            // Set NIS sebagai Text explicitly agar angka 0 di depan tidak hilang
            $sheet->setCellValueExplicit("B$r", $row[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C$r", $row[2]);
            $sheet->setCellValue("D$r", $row[3]);

            $sheet->getStyle("A$r:D$r")->applyFromArray($dataStyle);
            $sheet->getStyle("A$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Zebra background striping
            if ($i % 2 === 1) {
                $sheet->getStyle("A$r:D$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FE');
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // Format kolom NIS sebagai teks untuk seluruh kolom B (hingga baris 500)
        $sheet->getStyle('B5:B500')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // 5. Lebar Kolom yang Proporsional
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(22);

        return $spreadsheet;
    }

    /**
     * Generate export XLSX dari data siswa
     */
    public static function generateStudentExport(array $students): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->setShowGridLines(true);

        // Header Title
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'DAFTAR SISWA — CLASSROOM STAR');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['name' => 'Segoe UI', 'size' => 13, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10122A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Columns Header
        $headers = ['No', 'NIS', 'Nama Lengkap Siswa', 'Kelas', 'Total Bintang (⭐)'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '3', $h);
        }

        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['name' => 'Segoe UI', 'size' => 10, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E2240']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '444466']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension(3)->setRowHeight(24);

        foreach ($students as $i => $s) {
            $r = $i + 4;
            $sheet->setCellValue("A$r", $i + 1);
            $sheet->setCellValueExplicit("B$r", $s['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C$r", $s['name']);
            $sheet->setCellValue("D$r", $s['class_name'] ?? '-');
            $sheet->setCellValue("E$r", (int) ($s['total_stars'] ?? 0));

            $sheet->getStyle("A$r:E$r")->applyFromArray([
                'font' => ['name' => 'Segoe UI', 'size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E4F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($i % 2 === 1) {
                $sheet->getStyle("A$r:E$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FE');
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);

        return $spreadsheet;
    }

    /**
     * Stream Excel (.xlsx) ke browser (download)
     */
    public static function download(Spreadsheet $spreadsheet, string $filename): never
    {
        // Bersihkan seluruh level output buffer sebelumnya agar file binary tidak korup
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Pastikan nama file berakhiran .xlsx
        $basename = basename($filename);
        if (!str_ends_with(strtolower($basename), '.xlsx')) {
            $basename .= '.xlsx';
        }

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cs_export_' . bin2hex(random_bytes(8)) . '.xlsx';
        $writer   = new XlsxWriter($spreadsheet);
        $writer->save($tempPath);
        $filesize = filesize($tempPath);

        // Header HTTP standar RFC 6266 untuk download file XLSX
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $basename . '"; filename*=UTF-8\'\'' . rawurlencode($basename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $filesize);

        readfile($tempPath);
        @unlink($tempPath);
        exit;
    }
}
