<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Validasi keberadaan event
$result = mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id");
$event = mysqli_fetch_assoc($result);
if (!$event) {
    echo "Kegiatan tidak ditemukan.";
    exit;
}

$success = false;
$error = "";

if (isset($_POST['daftar'])) {
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $nama         = mysqli_real_escape_string($conn, $_POST['nama']);
    $nim          = mysqli_real_escape_string($conn, $_POST['nim']);
    $status_user  = $_POST['status_user'];
    $fakultas     = $_POST['fakultas'];
    $program_studi= $_POST['program_studi'];
    $nama_campus  = mysqli_real_escape_string($conn, $_POST['nama_kampus']);

    // Hitung pendaftar terdaftar saat ini untuk validasi kuota
    $count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM registrations WHERE event_id = $event_id");
    $count_row = mysqli_fetch_assoc($count_res);
    
    if ($count_row['total'] >= $event['kuota']) {
        $error = "Maaf, kuota pendaftaran untuk kegiatan ini sudah penuh!";
    } else {
        $query = "INSERT INTO registrations (event_id, email, nama, nim, status_user, fakultas, program_studi, nama_campus) 
                VALUES ($event_id, '$email', '$nama', '$nim', '$status_user', '$fakultas', '$program_studi', '$nama_campus')";
        
        if (mysqli_query($conn, $query)) {
            $success = true;
        } else {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Peserta - <?= htmlspecialchars($event['judul']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{
            --maroon:#5C1A2C; --maroon-2:#732339; --gold:#D2A75A; --gold-soft:#E8C989;
            --bg:#170A0D; --bg-2:#1D0D11; --surface:#231015; --ink:#F1E5E3;
            --slate:#B79CA1; --line:#3A2126;
        }
        body{ font-family:'Inter',sans-serif; background-color:var(--bg); }
        select{ color-scheme: dark; }
    </style>
</head>
<body class="text-[var(--ink)] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-[var(--surface)] border border-[var(--line)] rounded-2xl p-6 sm:p-8 shadow-2xl">
        <div class="mb-6 border-b border-[var(--line)] pb-4 flex justify-between items-center">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--gold)]">Formulir Pendaftaran &bull; Bebas Biaya</span>
                <h2 class="text-lg font-bold text-white mt-1"><?= htmlspecialchars($event['judul']) ?></h2>
            </div>
            <a href="admin.php" class="text-[var(--slate)] hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></a>
        </div>

        <?php if($success): ?>
            <div class="bg-emerald-950/40 border border-emerald-500 text-emerald-400 p-5 rounded-xl text-xs text-center mb-4 space-y-2 shadow">
                <i class="fa-solid fa-circle-check text-2xl mb-1 block"></i>
                <p class="font-bold text-white text-sm">Pendaftaran Sukses!</p>
                <p>Data Anda berhasil masuk ke dalam daftar peserta kegiatan terintegrasi.</p>
                <div class="pt-2">
                    <a href="admin.php" class="inline-block px-4 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-white font-bold rounded-lg transition no-underline">Kembali ke Beranda</a>
                </div>
            </div>
        <?php else: ?>
            
            <?php if($error != ""): ?>
                <div class="bg-rose-950/40 border border-rose-500 text-rose-400 p-3 rounded-xl text-xs mb-4">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Nama Lengkap</label>
                        <input type="text" name="nama" required placeholder="Masukkan nama sesuai KTP/KTM" class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Alamat Email</label>
                        <input type="email" name="email" required placeholder="Contoh: user@kampus.ac.id" class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">NIM / NIDN</label>
                        <input type="text" name="nim" placeholder="Nomor Induk Mahasiswa / Dosen" required class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Status Civitas</label>
                        <select name="status_user" required class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition cursor-pointer">
                            <option value="Mahasiswa">Mahasiswa</option>
                            <option value="Dosen">Dosen</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Asal Nama Kampus</label>
                    <input type="text" name="nama_kampus" placeholder="Contoh: Universitas Potensi Utama" required class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition">
                    <p class="text-[9px] text-[var(--slate)] mt-1 italic">*Menerima pendaftaran dari seluruh perguruan tinggi di Indonesia.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Fakultas (Khusus Potensi Utama)</label>
                        <select name="fakultas" required class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition cursor-pointer">
                            <option value="Fakultas Teknik dan Ilmu Komputer (FTIK)">Fakultas Teknik dan Ilmu Komputer (FTIK)</option>
                            <option value="Fakultas Bisnis dan Humaniora (FBH)">Fakultas Bisnis dan Humaniora (FBH)</option>
                            <option value="Fakultas Seni dan Desain (FSD)">Fakultas Seni dan Desain (FSD)</option>
                            <option value="Fakultas Psikologi">Fakultas Psikologi</option>
                            <option value="Fakultas Hukum">Fakultas Hukum</option>
                            <option value="Luar Kampus / Universitas Lain">Luar Kampus / Universitas Lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--gold-soft)] uppercase mb-1 tracking-wide">Program Studi</label>
                        <select name="program_studi" required class="w-full px-3 py-2 border border-[var(--line)] bg-[var(--bg-2)] rounded-xl text-xs text-[var(--ink)] focus:outline-none focus:border-[var(--gold)] transition cursor-pointer">
                            <optgroup label="Teknik & Ilmu Komputer (FTIK)">
                                <option value="Teknik Informatika">Teknik Informatika</option>
                                <option value="Sistem Informasi">Sistem Informasi</option>
                                <option value="Rekayasa Perangkat Lunak">Rekayasa Perangkat Lunak</option>
                                <option value="Teknologi Informasi">Teknologi Informasi</option>
                                <option value="Pendidikan Teknologi Informasi">Pendidikan Teknologi Informasi</option>
                            </optgroup>
                            <optgroup label="Bisnis & Humaniora (FBH)">
                                <option value="Manajemen">Manajemen</option>
                                <option value="Akuntansi">Akuntansi</option>
                                <option value="Ekonomi Syariah">Ekonomi Syariah</option>
                                <option value="Sastra Inggris">Sastra Inggris</option>
                                <option value="Hubungan Internasional">Hubungan Internasional</option>
                            </optgroup>
                            <optgroup label="Seni & Desain (FSD)">
                                <option value="Desain Komunikasi Visual (DKV)">Desain Komunikasi Visual (DKV)</option>
                                <option value="Desain Interior">Desain Interior</option>
                            </optgroup>
                            <optgroup label="Fakultas Lainnya">
                                <option value="Psikologi">Psikologi</option>
                                <option value="Hukum">Hukum</option>
                                <option value="Prodi Luar Kampus (Lainnya)">Prodi Luar Kampus (Lainnya)</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="pt-4 border-t border-[var(--line)] flex justify-end gap-3">
                    <a href="admin.php" class="px-4 py-2 bg-[var(--bg-2)] text-[var(--slate)] border border-[var(--line)] rounded-xl font-bold text-xs hover:text-white transition">Batal</a>
                    <button type="submit" name="daftar" class="px-5 py-2 bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white font-bold text-xs rounded-xl border-b-2 border-[var(--gold)] transition shadow">Kirim Pendaftaran</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>