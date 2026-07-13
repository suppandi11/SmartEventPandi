<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

require 'config.php';
require 'filter.php';

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';
$username = $_SESSION['username'];

if ($event_id <= 0 || $komentar === '') {
    echo json_encode(['success' => false, 'message' => 'Komentar tidak boleh kosong.']);
    exit;
}

if (mb_strlen($komentar) > 500) {
    echo json_encode(['success' => false, 'message' => 'Komentar maksimal 500 karakter.']);
    exit;
}

if (containsBadWords($komentar)) {
    echo json_encode(['success' => false, 'message' => 'Komentar mengandung kata yang tidak pantas. Mohon gunakan bahasa yang sopan.']);
    exit;
}

// Pastikan event benar-benar ada
$stmt = mysqli_prepare($conn, "SELECT id FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $event_id);
mysqli_stmt_execute($stmt);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Event tidak ditemukan.']);
    exit;
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "INSERT INTO comments (event_id, username, komentar) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'iss', $event_id, $username, $komentar);
mysqli_stmt_execute($stmt);
$new_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'comment' => [
        'id'       => $new_id,
        'username' => htmlspecialchars($username, ENT_QUOTES),
        'komentar' => nl2br(htmlspecialchars($komentar, ENT_QUOTES)),
    ],
]);