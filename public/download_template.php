<?php
/**
 * Direct Download Template Excel (.xlsx)
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireRole(['admin', 'teacher']);

$spreadsheet = XlsxHelper::generateImportTemplate();
XlsxHelper::download($spreadsheet, 'template_import_siswa.xlsx');
