<?php
/**
 * API: Levels — batch update
 * POST /api/levels.php  { levels: [{id, predicate, min_percent, max_percent, additional_score}] }
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');
Response::requireMethod('POST');

$body   = Response::jsonBody();
$levels = $body['levels'] ?? [];

if (empty($levels) || !is_array($levels)) {
    Response::error('Data level tidak valid.', 400);
}

try {
    Database::beginTransaction();
    foreach ($levels as $lv) {
        $id = (int)($lv['id'] ?? 0);
        if (!$id) continue;
        Database::execute(
            'UPDATE levels SET predicate = ?, min_percent = ?, max_percent = ?, additional_score = ? WHERE id = ?',
            [
                trim($lv['predicate'] ?? ''),
                (float)($lv['min_percent'] ?? 0),
                (float)($lv['max_percent'] ?? 0),
                (int)($lv['additional_score'] ?? 0),
                $id,
            ]
        );
    }
    Database::commit();
    Response::json(['message' => 'Level berhasil diperbarui.']);
} catch (Throwable $e) {
    Database::rollback();
    Response::error('Gagal menyimpan: ' . $e->getMessage(), 500);
}
