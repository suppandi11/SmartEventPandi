<?php
session_start();
require 'config.php';

if (isset($_SESSION['login'])) {
    header("Location: admin.php");
    exit;
}

$error = false;

$wa_message = "Hallo kak, saya mau daftar akun Smart Event\nPilih satu: Mahasiswa / Dosen :\nNama :\nNIM :";
$wa_link = "https://wa.me/6283822124957?text=" . rawurlencode($wa_message);

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if ($password === $row['password']) {
            $_SESSION['login']    = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            header("Location: admin.php");
            exit;
        }
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Smart Event</title>
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
        input:focus{ box-shadow: 0 0 0 3px rgba(210,167,90,0.18); }
    </style>
</head>
<body class="text-[var(--ink)] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-[#EEF2F3] rounded-xl px-4 py-2 shadow-sm mb-4">
                <img src="img/logo-pandi.png" alt="Pandi Smart Event" class="h-10 w-auto block">
            </div>
            <p class="text-[10px] letter-eyebrow uppercase text-[var(--gold-soft)] font-semibold">Kampus &middot; Smart Event</p>
        </div>

        <div class="bg-[var(--surface)] border border-[var(--line)] rounded-2xl p-6 sm:p-8 shadow-[0_2px_20px_rgba(0,0,0,0.45)]">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-[var(--maroon)] border-b-2 border-[var(--gold)] rounded-xl text-white flex items-center justify-center text-xl mx-auto shadow-md shadow-black/40 mb-3">
                    <i class="fa-solid fa-circle-nodes"></i>
                </div>
                <h2 class="text-xl font-display font-semibold text-white tracking-tight">Selamat Datang</h2>
                <p class="text-xs text-[var(--slate)] mt-1">Silakan masuk untuk mengakses sistem agenda kampus</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 bg-[#3A1414] border border-[#5C2020] text-[#F0A0A0] p-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-sm"></i>
                    <span>Username atau Password salah!</span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[var(--gold-soft)] mb-1.5">Nama Pengguna (Username)</label>
                    <div class="relative">
                        <input type="text" name="username" required placeholder="Masukkan username..." class="w-full pl-10 pr-4 py-2.5 border border-[var(--line)] rounded-xl bg-[var(--bg-2)] focus:outline-none focus:border-[var(--gold)] text-sm text-[var(--ink)] placeholder:text-[var(--slate-soft)]">
                        <div class="absolute left-3.5 top-3.5 text-[var(--slate-soft)] text-xs"><i class="fa-solid fa-user"></i></div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[var(--gold-soft)] mb-1.5">Kata Sandi (Password)</label>
                    <div class="relative">
                        <input type="password" name="password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-2.5 border border-[var(--line)] rounded-xl bg-[var(--bg-2)] focus:outline-none focus:border-[var(--gold)] text-sm text-[var(--ink)] placeholder:text-[var(--slate-soft)]">
                        <div class="absolute left-3.5 top-3.5 text-[var(--slate-soft)] text-xs"><i class="fa-solid fa-lock"></i></div>
                    </div>
                </div>
                <button type="submit" name="login" class="w-full py-2.5 bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white font-bold text-xs rounded-xl transition uppercase tracking-wider shadow-md shadow-black/40 cursor-pointer border-b-2 border-[var(--gold)]">Masuk Sistem</button>
            </form>

            <div class="mt-5 bg-[var(--gold-bg)] border border-[var(--gold)]/30 rounded-xl p-3.5 flex items-start gap-2.5">
                <i class="fa-solid fa-shield-halved text-[var(--gold)] text-sm mt-0.5"></i>
                <p class="text-[11px] text-[var(--gold-soft)] leading-relaxed">
                    Untuk menjaga keamanan sistem, jika belum memiliki akun, harap konfirmasi terlebih dahulu melalui admin.
                    <a href="<?= $wa_link ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-bold text-white bg-[#25D366] hover:bg-[#1ebe57] px-2.5 py-1 rounded-lg mt-2 transition no-underline">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp Admin
                    </a>
                </p>
            </div>
        </div>

        <p class="text-center text-[10px] text-[var(--slate-soft)] mt-5">&copy; <?= date('Y') ?> Smart Event Kampus</p>
    </div>
</body>
</html>
