<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat menghapus komentar.']);
    exit;
}

require 'config.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID komentar tidak valid.']);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$ok = mysqli_stmt_affected_rows($stmt) > 0;
mysqli_stmt_close($stmt);

echo json_encode(['success' => $ok, 'message' => $ok ? '' : 'Komentar tidak ditemukan.']);