<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
require 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM events WHERE id = $id")) { header("Location: admin.php"); exit; }
}
header("Location: admin.php");
exit;
?>