<?php
$host = "localhost";         // localhost jadi MySQL Hostname InfinityFree
$user = "root";                   // root jadi Username InfinityFree kamu
$pass = "";                   // Password dari akun MySQL InfinityFree
$db   = "db_smart_event";    // (db_smart_event) Nama Database InfinityFree

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>