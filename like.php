<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

require 'config.php';

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$username = $_SESSION['username'];

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Event tidak valid.']);
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

// Cek apakah user ini sudah like event ini
$stmt = mysqli_prepare($conn, "SELECT id FROM likes WHERE event_id = ? AND username = ?");
mysqli_stmt_bind_param($stmt, 'is', $event_id, $username);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($existing) {
    // Sudah like -> unlike (toggle off)
    $del = mysqli_prepare($conn, "DELETE FROM likes WHERE id = ?");
    mysqli_stmt_bind_param($del, 'i', $existing['id']);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
    $liked = false;
} else {
    // Belum like -> like (toggle on)
    $ins = mysqli_prepare($conn, "INSERT INTO likes (event_id, username) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, 'is', $event_id, $username);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
    $liked = true;
}

$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM likes WHERE event_id = ?");
mysqli_stmt_bind_param($count_stmt, 'i', $event_id);
mysqli_stmt_execute($count_stmt);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
mysqli_stmt_close($count_stmt);

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $total]);