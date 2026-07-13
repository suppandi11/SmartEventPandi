<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
require 'config.php';

if (isset($_POST['simpan'])) {
    $judul     = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori  = $_POST['kategori'];
    $tanggal   = $_POST['tanggal'];
    $waktu     = $_POST['waktu'];
    $lokasi    = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kuota     = intval($_POST['kuota']);

    $query = "INSERT INTO events (judul, kategori, tanggal, waktu, lokasi, deskripsi, kuota) VALUES ('$judul', '$kategori', '$tanggal', '$waktu', '$lokasi', '$deskripsi', '$kuota')";
    if (mysqli_query($conn, $query)) { header("Location: admin.php"); exit; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Event - Smart Event</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --maroon:#5C1A2C;
            --maroon-2:#732339;
            --maroon-3:#3B0F1B;
            --gold:#D2A75A;
            --gold-soft:#E8C989;
            --gold-bg:#332212;

            --bg:#170A0D;
            --bg-2:#1D0D11;
            --surface:#231015;
            --surface-2:#2B141A;
            --ink:#F1E5E3;
            --slate:#B79CA1;
            --slate-soft:#7C6469;
            --line:#3A2126;
            --line-soft:#2C161C;
        }
        body{
            font-family:'Inter',sans-serif;
            background-color:var(--bg);
            background-image: radial-gradient(circle at 1px 1px, rgba(210,167,90,0.07) 1px, transparent 0);
            background-size:22px 22px;
        }
        .font-display{ font-family:'Fraunces','Inter',serif; }
        .letter-eyebrow{ letter-spacing:0.16em; }
        input:focus, select:focus, textarea:focus{ box-shadow: 0 0 0 3px rgba(210,167,90,0.18); }
        select{ color-scheme: dark; }
    </style>
</head>
<body class="text-[var(--ink)] min-h-screen flex flex-col">

    <header class="bg-[var(--bg-2)] sticky top-0 z-50 border-b-[3px] border-[var(--gold)] shadow-[0_2px_18px_-6px_rgba(0,0,0,0.6)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 h-[68px] flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="bg-[#EEF2F3] rounded-xl px-3 py-1.5 shadow-sm shrink-0">
                    <img src="img/logo-pandi.png" alt="Pandi Smart Event" class="h-8 sm:h-9 w-auto block">
                </div>
                <p class="text-[10px] letter-eyebrow uppercase text-[var(--gold-soft)] font-semibold hidden sm:block">Kampus &middot; Admin</p>
            </div>
            <a href="admin.php" class="bg-[var(--surface-2)] hover:bg-[var(--maroon)] text-[var(--slate)] hover:text-white px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-[var(--line)]">
                <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Kembali</span>
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-10 flex-1 w-full">
        <p class="text-[11px] font-bold letter-eyebrow uppercase text-[var(--gold)] mb-1">Panel Manajemen</p>
        <h2 class="text-2xl sm:text-[28px] font-display font-semibold text-white tracking-tight leading-none mb-6">Tambah Kegiatan Baru</h2>

        <div class="bg-[var(--surface)] border border-[var(--line)] rounded-2xl p-5 sm:p-7 shadow-[0_2px_10px_rgba(0,0,0,0.35)]">
            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Nama Event</label>
                    <input type="text" name="judul" required placeholder="Contoh: Seminar Nasional Teknologi" class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] placeholder:text-[var(--slate-soft)] focus:outline-none focus:border-[var(--gold)]">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Kategori</label>
                        <select name="kategori" required class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] focus:outline-none focus:border-[var(--gold)]">
                            <option value="Seminar">Seminar</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Lomba">Lomba</option>
                            <option value="Pelatihan">Pelatihan</option>
                            <option value="Webinar">Webinar</option>
                            <option value="Pameran">Pameran</option>
                            <option value="Kuliah Umum">Kuliah Umum</option>
                            <option value="Budaya">Budaya</option>
                            <option value="Olahraga">Olahraga</option>
                            <option value="Organisasi">Organisasi</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Lokasi</label>
                        <input type="text" name="lokasi" required placeholder="Contoh: Aula Gedung A" class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] placeholder:text-[var(--slate-soft)] focus:outline-none focus:border-[var(--gold)]">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] focus:outline-none focus:border-[var(--gold)]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Waktu</label>
                        <input type="time" name="waktu" required class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] focus:outline-none focus:border-[var(--gold)]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Kuota Peserta</label>
                        <input type="number" name="kuota" value="50" min="1" required class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] focus:outline-none focus:border-[var(--gold)]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-[var(--gold-soft)] mb-1.5">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" required placeholder="Jelaskan detail kegiatan..." class="w-full px-4 py-2.5 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-sm text-[var(--ink)] placeholder:text-[var(--slate-soft)] focus:outline-none focus:border-[var(--gold)] resize-none"></textarea>
                </div>
                <div class="flex gap-3 justify-end pt-5 border-t border-[var(--line-soft)]">
                    <a href="admin.php" class="px-5 py-2.5 bg-[var(--surface-2)] hover:bg-[var(--line)] text-[var(--slate)] rounded-xl font-bold text-xs transition border border-[var(--line)]">Batal</a>
                    <button type="submit" name="simpan" class="px-5 py-2.5 bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white rounded-xl font-bold text-xs cursor-pointer transition shadow-md shadow-black/40 border-b-2 border-[var(--gold)]">
                        <i class="fa-solid fa-check mr-1"></i> Simpan Event
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>