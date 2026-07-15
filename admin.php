<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM events";
if ($search != '') {
    $query .= " WHERE judul LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%'";
}
$query .= " ORDER BY tanggal DESC";
$result = mysqli_query($conn, $query);

$isAdmin = ($_SESSION['role'] === 'admin');

/**
 * Warna badge kategori — hanya untuk tampilan, tidak menyentuh data.
 * Disetel untuk latar gelap (bg gelap jenuh + teks terang senada).
 * Kategori baru yang belum terdaftar otomatis dapat warna dari
 * rotasi palet cadangan, konsisten tiap kali kategori itu muncul.
 */
function getCategoryStyle($kategori) {
    static $map = [
        'lomba'        => ['bg' => '#3A2210', 'text' => '#F0A868', 'border' => '#5A3418'],
        'kompetisi'    => ['bg' => '#3A2210', 'text' => '#F0A868', 'border' => '#5A3418'],
        'seminar'      => ['bg' => '#12283A', 'text' => '#7EC0EA', 'border' => '#1E4560'],
        'webinar'      => ['bg' => '#0F2E33', 'text' => '#6FD8E5', 'border' => '#1B4B52'],
        'workshop'     => ['bg' => '#2A1D3E', 'text' => '#C6A6F0', 'border' => '#402D5C'],
        'pelatihan'    => ['bg' => '#123028', 'text' => '#6EDCB8', 'border' => '#1E4C3E'],
        'pameran'      => ['bg' => '#3A1522', 'text' => '#F0A0C0', 'border' => '#5C2338'],
        'kuliah umum'  => ['bg' => '#201C3E', 'text' => '#B0A8F0', 'border' => '#34305C'],
        'budaya'       => ['bg' => '#331F14', 'text' => '#E0B088', 'border' => '#4E3020'],
        'olahraga'     => ['bg' => '#232D12', 'text' => '#B8D080', 'border' => '#3A4A1E'],
        'organisasi'   => ['bg' => '#251A3E', 'text' => '#C0A8F0', 'border' => '#3A2C5C'],
        'lainnya'      => ['bg' => '#2A2226', 'text' => '#C4B4B8', 'border' => '#3E3236'],
    ];
    $key = strtolower(trim($kategori));
    if (isset($map[$key])) return $map[$key];

    $fallback = [
        ['bg' => '#331824', 'text' => '#E497B6', 'border' => '#4C2434'],
        ['bg' => '#12233A', 'text' => '#8FB4EA', 'border' => '#1E3A5C'],
        ['bg' => '#312A12', 'text' => '#E0C877', 'border' => '#4A3F1B'],
        ['bg' => '#132A22', 'text' => '#7FCBA8', 'border' => '#1E4436'],
        ['bg' => '#33200F', 'text' => '#E7A578', 'border' => '#4E3019'],
    ];
    $idx = abs(crc32($key)) % count($fallback);
    return $fallback[$idx];
}

$events_data = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events_data[] = $row;
    }
}

// ==== Ambil data like & komentar untuk semua event yang tampil ====
$likesCount  = [];
$likedByMe   = [];
$commentsData = [];
$registrantsData = [];

if (!empty($events_data)) {
    $event_ids = array_map(fn($r) => (int)$r['id'], $events_data);
    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    $types = str_repeat('i', count($event_ids));

    // Total like per event
    $stmt = mysqli_prepare($conn, "SELECT event_id, COUNT(*) as total FROM likes WHERE event_id IN ($placeholders) GROUP BY event_id");
    mysqli_stmt_bind_param($stmt, $types, ...$event_ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $likesCount[(int)$r['event_id']] = (int)$r['total'];
    }
    mysqli_stmt_close($stmt);

    // Event mana saja yang sudah di-like user yang sedang login
    $stmt = mysqli_prepare($conn, "SELECT event_id FROM likes WHERE username = ? AND event_id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, 's' . $types, $_SESSION['username'], ...$event_ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $likedByMe[(int)$r['event_id']] = true;
    }
    mysqli_stmt_close($stmt);

    // Semua komentar untuk event yang tampil
    $stmt = mysqli_prepare($conn, "SELECT * FROM comments WHERE event_id IN ($placeholders) ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt, $types, ...$event_ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $commentsData[(int)$r['event_id']][] = $r;
    }
    mysqli_stmt_close($stmt);

    // Semua pendaftar (registrasi) untuk event yang tampil
    $stmt = mysqli_prepare($conn, "SELECT * FROM registrations WHERE event_id IN ($placeholders) ORDER BY registered_at ASC");
    mysqli_stmt_bind_param($stmt, $types, ...$event_ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $registrantsData[(int)$r['event_id']][] = $r;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEvent-Suppandi</title>
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

            --success:#5FCB98;
            --success-bg:#123024;
            --done-text:#A69AA0;
            --done-bg:#241A1D;
        }
        body{
            font-family:'Inter',sans-serif;
            background-color:var(--bg);
            background-image: radial-gradient(circle at 1px 1px, rgba(210,167,90,0.07) 1px, transparent 0);
            background-size:22px 22px;
        }
        .font-display{ font-family:'Fraunces','Inter',serif; }
        .font-mono{ font-family:'JetBrains Mono',monospace; }
        .letter-eyebrow{ letter-spacing:0.16em; }

        .ticket-card{ position:relative; }
        .ticket-card::before{
            content:""; position:absolute; left:96px; top:0; bottom:0;
            width:0; border-left:1.5px dashed var(--line);
        }
        .ticket-card .punch{
            position:absolute; left:90px; width:12px; height:12px;
            background:var(--bg); border:1.5px solid var(--line); border-radius:9999px;
            transform:translateX(-50%);
        }
        .status-dot{ width:6px; height:6px; border-radius:9999px; display:inline-block; }
        .cat-badge{
            display:inline-block; padding:2px 9px; font-size:10px; font-weight:700;
            border-radius:6px; text-transform:uppercase; letter-spacing:0.04em;
            border:1px solid transparent;
        }
        .like-btn.is-liked{ background-color:#3A2210; border-color:var(--gold); color:var(--gold-soft); }
        .like-btn{ background-color:var(--bg-2); border-color:var(--line); color:var(--slate); }
        .free-badge{
            display:inline-flex; align-items:center; gap:4px; padding:2px 9px; font-size:10px; font-weight:700;
            border-radius:6px; text-transform:uppercase; letter-spacing:0.04em;
            background-color:var(--success-bg); color:var(--success); border:1px solid transparent;
        }
        .daftar-btn{
            background-color:var(--gold-bg); color:var(--gold-soft); border-color:rgba(210,167,90,0.3);
        }
        .peserta-btn{ background-color:var(--bg-2); border-color:var(--line); color:var(--slate); }

        @media print {
            @page { size: A4 portrait; margin: 18mm 15mm 18mm 15mm; }
            body { background:#ffffff !important; color:#000000 !important; font-family:'Inter',Helvetica,Arial,sans-serif !important; font-size:10.5pt !important; }
            .no-print{ display:none !important; }
            .print-container{ width:100% !important; max-width:100% !important; padding:0 !important; margin:0 !important; }
            .print-card{ border:1px solid #94a3b8 !important; border-radius:0px !important; box-shadow:none !important; background:#ffffff !important; }
            tr:nth-child(even){ background-color:#f8fafc !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            th{ background-color:#f3e9e6 !important; color:#2A0A12 !important; border-bottom:2px solid #3B0F1B !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            td{ border-bottom:1px solid #e2e8f0 !important; color:#1f2433 !important; padding:11px 10px !important; }
            .print-title{ font-size:15pt !important; color:#000000 !important; font-family:'Fraunces',serif !important; }
            .cat-badge{ border:1px solid #cbd5e1 !important; background:transparent !important; color:#000000 !important; padding:2px 7px !important; font-size:8.5pt !important; }
        }
    </style>
</head>
<body class="text-[var(--ink)] min-h-screen flex flex-col">

    <header class="bg-[var(--bg-2)] sticky top-0 z-50 no-print border-b-[3px] border-[var(--gold)] shadow-[0_2px_18px_-6px_rgba(0,0,0,0.6)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[68px] flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="bg-[#EEF2F3] rounded-xl px-3 py-1.5 shadow-sm shrink-0">
                    <img src="img/logo-pandi.png" alt="Pandi Smart Event" class="h-8 sm:h-9 w-auto block">
                </div>
                <p class="text-[10px] letter-eyebrow uppercase text-[var(--gold-soft)] font-semibold hidden sm:block">Kampus &middot; <?= ucfirst($_SESSION['role']) ?></p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex-col text-right hidden sm:flex">
                    <span class="text-[10px] text-[var(--slate)] uppercase tracking-widest font-bold">Pengguna</span>
                    <span class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
                <a href="logout.php" class="bg-[var(--surface-2)] hover:bg-[var(--maroon)] text-[var(--slate)] hover:text-white px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-[var(--line)]">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </header>

    <div class="hidden print:block text-center mb-8 border-b-[3px] border-double border-[#3B0F1B] pb-5">
        <h1 class="print-title text-2xl font-bold uppercase tracking-wide">Laporan Agenda dan Kegiatan Kampus</h1>
        <p class="text-sm font-medium text-slate-700 tracking-wide mt-1">Informasi Manajemen Platform</p>
        <div class="flex justify-between items-center mt-4 px-2 text-[11px] text-slate-600 font-mono">
            <span>Dicetak oleh: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
            <span>Tanggal Cetak: <?= date('d F Y / H:i') ?> WIB</span>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-7 sm:py-9 flex-1 w-full print-container">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-7 no-print">
            <div>
                <p class="text-[11px] font-bold letter-eyebrow uppercase text-[var(--gold)] mb-1">Panel Manajemen</p>
                <h2 class="text-2xl sm:text-[28px] font-display font-semibold text-white tracking-tight leading-none">Agenda Kegiatan Kampus</h2>
                <p class="text-xs text-[var(--slate)] mt-2">
                    <?= $isAdmin ? 'Kamu di sini sebagai admin, yang mengatur seluruh kegiatan.' : 'Kamu di sini sebagai mahasiswa, hanya melihat informasi kegiatan yang sudah ditetapkan.'; ?>
                </p>
            </div>

            <div class="flex gap-2.5 w-full sm:w-auto">
                <button onclick="window.print()" class="flex-1 sm:flex-none text-center bg-[var(--surface)] hover:bg-[var(--surface-2)] text-[var(--gold-soft)] px-4.5 py-2.5 rounded-lg font-bold text-xs transition border-[1.5px] border-[var(--line)] flex items-center justify-center gap-2 cursor-pointer uppercase tracking-wider">
                    <i class="fa-solid fa-print text-sm"></i> PDF
                </button>

                <?php if ($isAdmin): ?>
                    <a href="tambah.php" class="flex-1 sm:w-auto text-center bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white px-4.5 py-2.5 rounded-lg font-bold text-xs transition shadow-md shadow-black/40 flex items-center justify-center gap-2 cursor-pointer uppercase tracking-wider border-b-2 border-[var(--gold)]">
                        <i class="fa-solid fa-plus text-sm"></i> Tambah Kegiatan
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-7 bg-[var(--surface)] p-3.5 sm:p-4 rounded-xl border border-[var(--line)] shadow-[0_1px_2px_rgba(0,0,0,0.3)] no-print">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-2.5 w-full">
                <div class="relative flex-1">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama event, lokasi, atau kategori..." class="w-full pl-10 pr-4 py-2.5 border border-[var(--line)] rounded-lg focus:ring-2 focus:ring-[var(--gold)]/30 focus:border-[var(--gold)] focus:outline-none text-xs text-[var(--ink)] bg-[var(--bg-2)] placeholder:text-[var(--slate-soft)]">
                    <div class="absolute left-3.5 top-3 text-[var(--slate-soft)] text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="flex-1 sm:flex-none bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white px-5 py-2.5 rounded-lg text-xs font-bold transition cursor-pointer text-center">Cari</button>
                    <?php if($search != ''): ?>
                        <a href="admin.php" class="flex-1 sm:flex-none bg-[var(--surface-2)] hover:bg-[var(--line)] text-[var(--slate)] px-4 py-2.5 rounded-lg text-xs font-bold transition flex items-center justify-center border border-[var(--line)]">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- MOBILE: ticket-stub cards -->
        <div class="block lg:hidden space-y-4 no-print">
            <?php if(!empty($events_data)): $no = 1; ?>
                <?php foreach($events_data as $row):
                    $is_aktif = ($row['tanggal'] >= date('Y-m-d'));
                    $day_num = date('d', strtotime($row['tanggal']));
                    $mon_short = date('M', strtotime($row['tanggal']));
                    $yr_short = date('Y', strtotime($row['tanggal']));
                    $cat = getCategoryStyle($row['kategori']);
                    $eid = (int)$row['id'];
                    $lc = $likesCount[$eid] ?? 0;
                    $liked = isset($likedByMe[$eid]);
                    $comments = $commentsData[$eid] ?? [];
                    $registrants = $registrantsData[$eid] ?? [];
                    $rc = count($registrants);
                ?>
                    <div class="ticket-card bg-[var(--surface)] rounded-2xl border border-[var(--line)] shadow-[0_2px_10px_rgba(0,0,0,0.35)] overflow-hidden">
                        <div class="punch" style="top:-6px;"></div>
                        <div class="punch" style="bottom:-6px;"></div>
                        <div class="flex">
                            <div class="w-[96px] shrink-0 bg-gradient-to-b from-[var(--maroon)] to-[var(--maroon-3)] text-white flex flex-col items-center justify-center py-5">
                                <span class="font-display text-3xl font-bold leading-none"><?= $day_num ?></span>
                                <span class="text-[10px] uppercase tracking-widest text-[var(--gold-soft)] font-bold mt-1.5"><?= $mon_short ?></span>
                                <span class="text-[9px] text-[#D9B8C0] mt-0.5"><?= $yr_short ?></span>
                            </div>
                            <div class="flex-1 p-4 space-y-3 min-w-0">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <span class="text-[9px] font-bold text-[var(--slate-soft)] letter-eyebrow uppercase">Kegiatan <?= $no++ ?></span>
                                        <h3 class="font-display font-semibold text-[var(--gold-soft)] text-[15px] leading-snug mt-0.5"><?= htmlspecialchars($row['judul']) ?></h3>
                                    </div>
                                    <?php if($is_aktif): ?>
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-bold rounded-full bg-[var(--success-bg)] text-[var(--success)] uppercase tracking-wider"><span class="status-dot bg-[var(--success)]"></span>Mendatang</span>
                                    <?php else: ?>
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-bold rounded-full bg-[var(--done-bg)] text-[var(--done-text)] uppercase tracking-wider"><span class="status-dot bg-[var(--done-text)]"></span>Selesai</span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-[var(--slate)] text-[11px] leading-relaxed line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>

                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="cat-badge" style="background-color:<?= $cat['bg'] ?>;color:<?= $cat['text'] ?>;border-color:<?= $cat['border'] ?>;"><?= htmlspecialchars($row['kategori']) ?></span>
                                    <span class="free-badge"><i class="fa-solid fa-circle-check"></i> Gratis / Bebas Biaya</span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-[11px] pt-3 border-t border-dashed border-[var(--line)]">
                                    <div>
                                        <div class="text-[var(--slate-soft)] font-semibold text-[10px] uppercase tracking-wide mb-1">Jam</div>
                                        <div class="font-mono font-semibold text-[var(--ink)]"><i class="fa-regular fa-clock mr-1 text-[var(--gold)]"></i><?= date('H:i', strtotime($row['waktu'])) ?> WIB</div>
                                    </div>
                                    <div>
                                        <div class="text-[var(--slate-soft)] font-semibold text-[10px] uppercase tracking-wide mb-1">Tempat</div>
                                        <div class="font-semibold text-[var(--ink)] truncate"><i class="fa-solid fa-location-dot mr-1 text-[var(--gold)]"></i><?= htmlspecialchars($row['lokasi']) ?></div>
                                    </div>
                                </div>
                                <div class="text-[10px] font-mono font-bold text-[var(--gold-soft)] bg-[var(--bg-2)] inline-block px-2 py-1 rounded border border-[var(--line)]"><i class="fa-solid fa-users mr-1 text-[var(--gold)]"></i>Terdaftar: <?= $rc ?> / <?= isset($row['kuota']) ? $row['kuota'] : '50' ?> Peserta</div>

                                <!-- Daftar & Peserta -->
                                <div class="flex items-center gap-2">
                                    <a href="daftar_event.php?event_id=<?= $eid ?>" class="daftar-btn flex-1 text-center flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-bold transition">
                                        <i class="fa-solid fa-pen-nib"></i> Daftar Kegiatan
                                    </a>
                                    <button type="button" onclick="toggleRegistrants(<?= $eid ?>)" class="peserta-btn flex items-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-bold transition">
                                        <i class="fa-solid fa-user-group"></i> <span class="peserta-count-label" data-event="<?= $eid ?>"><?= $rc ?></span>
                                    </button>
                                </div>
                                <div id="registrants-panel-<?= $eid ?>" class="registrants-panel hidden bg-[var(--bg-2)] rounded-lg border border-[var(--line-soft)] p-2.5 space-y-1.5 max-h-52 overflow-y-auto">
                                    <?php foreach ($registrants as $p): ?>
                                        <div class="registrant-item bg-[var(--surface)] rounded-lg px-3 py-2 border border-[var(--line-soft)]">
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="text-[11px] font-bold text-[var(--gold-soft)]"><?= htmlspecialchars($p['nama']) ?></span>
                                                <span class="text-[9px] font-bold uppercase text-[var(--slate)]"><?= htmlspecialchars($p['status_user']) ?></span>
                                            </div>
                                            <p class="text-[10px] text-[var(--slate)] mt-0.5"><?= htmlspecialchars($p['nama_campus']) ?> &middot; <?= htmlspecialchars($p['program_studi']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($registrants)): ?>
                                        <p class="text-[10px] italic text-[var(--slate-soft)]">Belum ada peserta terdaftar.</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Like & Komentar -->
                                <div class="pt-3 border-t border-[var(--line-soft)] space-y-3" data-event-id="<?= $eid ?>">
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="toggleLike(<?= $eid ?>, this)" class="like-btn <?= $liked ? 'is-liked' : '' ?> flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold transition" data-liked="<?= $liked ? '1' : '0' ?>">
                                            <span class="like-emoji"><?= $liked ? '🔥' : '🤍' ?></span>
                                            <span class="like-count"><?= $lc ?></span>
                                        </button>
                                        <button type="button" onclick="toggleComments(<?= $eid ?>)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[var(--line)] bg-[var(--bg-2)] text-[var(--slate)] text-xs font-bold">
                                            <i class="fa-regular fa-comment"></i> <span class="comment-count-label" data-event="<?= $eid ?>"><?= count($comments) ?></span> Komentar
                                        </button>
                                    </div>

                                    <div id="comments-panel-<?= $eid ?>" class="comments-panel hidden space-y-2.5">
                                        <div class="comment-list space-y-2 max-h-56 overflow-y-auto pr-1">
                                            <?php foreach ($comments as $c): ?>
                                                <div class="comment-item bg-[var(--bg-2)] rounded-lg px-3 py-2 border border-[var(--line-soft)]" data-comment-id="<?= $c['id'] ?>">
                                                    <div class="flex justify-between items-start gap-2">
                                                        <span class="text-[10px] font-bold text-[var(--gold-soft)]"><?= htmlspecialchars($c['username']) ?></span>
                                                        <?php if ($isAdmin): ?>
                                                            <button type="button" onclick="deleteComment(<?= $c['id'] ?>, <?= $eid ?>)" class="text-[#F0A0A0] text-[10px] hover:underline">Hapus</button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-[11px] text-[var(--ink)] mt-0.5"><?= nl2br(htmlspecialchars($c['komentar'])) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($comments)): ?>
                                                <p class="no-comment-msg text-[10px] italic text-[var(--slate-soft)]">Belum ada komentar.</p>
                                            <?php endif; ?>
                                        </div>
                                        <form onsubmit="return submitComment(event, <?= $eid ?>)" class="flex gap-2">
                                            <input type="text" name="komentar" placeholder="Tulis komentar..." maxlength="500" required class="flex-1 px-3 py-2 rounded-lg border border-[var(--line)] bg-[var(--bg-2)] text-[11px] text-[var(--ink)] placeholder:text-[var(--slate-soft)] focus:outline-none focus:border-[var(--gold)]">
                                            <button type="submit" class="px-3.5 py-2 bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white rounded-lg text-xs font-bold">Kirim</button>
                                        </form>
                                        <p class="comment-error text-[10px] text-[#F0A0A0] hidden"></p>
                                    </div>
                                </div>

                                <?php if ($isAdmin): ?>
                                    <div class="flex gap-2 pt-3 border-t border-[var(--line-soft)]">
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="flex-1 text-center text-[var(--gold-soft)] font-bold py-2 bg-[var(--gold-bg)] hover:bg-[#3E2A16] rounded-lg border border-[var(--gold)]/30 text-xs transition">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                        </a>
                                        <a href="hapus.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus kegiatan ini?')" class="flex-1 text-center text-[#F0A0A0] font-bold py-2 bg-[#3A1414] hover:bg-[#4A1A1A] rounded-lg border border-[#5C2020] text-xs transition">
                                            <i class="fa-solid fa-trash mr-1"></i> Hapus
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-14 bg-[var(--surface)] rounded-2xl border border-[var(--line)] text-[var(--slate-soft)] text-xs italic">
                    <i class="fa-regular fa-folder-open text-2xl block mb-2 text-[var(--slate-soft)]"></i>
                    Tidak ada agenda kegiatan ditemukan.
                </div>
            <?php endif; ?>
        </div>

        <!-- DESKTOP / PRINT: table -->
        <div class="hidden lg:block print:block bg-[var(--surface)] rounded-2xl border border-[var(--line)] overflow-hidden print-card shadow-[0_2px_10px_rgba(0,0,0,0.35)]">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="bg-[var(--bg-2)] border-b-2 border-[var(--gold)]/25 text-[10px] font-bold uppercase text-[var(--gold-soft)] letter-eyebrow">
                            <th class="px-3 py-3.5 w-[70px]">Tanggal</th>
                            <th class="px-3 py-3.5">Nama &amp; Deskripsi</th>
                            <th class="px-3 py-3.5 w-[110px]">Kategori</th>
                            <th class="px-3 py-3.5 w-[140px]">Lokasi &amp; Kuota</th>
                            <th class="px-3 py-3.5 w-[118px] text-center print:table-cell">Status</th>
                            <th class="px-3 py-3.5 w-[120px] text-center no-print">Reaksi</th>
                            <?php if ($isAdmin): ?>
                                <th class="px-3 py-3.5 text-center w-[76px] no-print">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line-soft)] text-xs">
                        <?php if(!empty($events_data)): ?>
                            <?php
                            $colspan = 6 + ($isAdmin ? 1 : 0);
                            foreach($events_data as $row):
                                $is_aktif = ($row['tanggal'] >= date('Y-m-d'));
                                $status_text = $is_aktif ? 'Mendatang' : 'Selesai';
                                $day_num = date('d', strtotime($row['tanggal']));
                                $mon_short = date('M', strtotime($row['tanggal']));
                                $yr_short = date('Y', strtotime($row['tanggal']));
                                $cat = getCategoryStyle($row['kategori']);
                                $eid = (int)$row['id'];
                                $lc = $likesCount[$eid] ?? 0;
                                $liked = isset($likedByMe[$eid]);
                                $comments = $commentsData[$eid] ?? [];
                                $registrants = $registrantsData[$eid] ?? [];
                                $rc = count($registrants);
                            ?>
                                <tr class="hover:bg-[var(--surface-2)]/60 transition group">
                                    <td class="px-3 py-3.5 align-top overflow-hidden print:text-slate-900">
                                        <div class="leading-tight">
                                            <div class="font-display text-lg font-bold text-[var(--gold-soft)] leading-none print:text-base print:text-slate-900"><?= $day_num ?> <span class="text-[10px] font-sans font-bold uppercase text-[var(--gold)] print:text-slate-700"><?= $mon_short ?></span></div>
                                            <div class="text-[9px] text-[var(--slate-soft)] mt-0.5"><?= $yr_short ?></div>
                                            <div class="text-[10px] font-mono font-semibold text-[var(--slate)] mt-1.5 print:text-slate-600"><i class="fa-regular fa-clock mr-0.5 print:hidden"></i><?= date('H:i', strtotime($row['waktu'])) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 align-top overflow-hidden">
                                        <div class="font-display font-semibold text-[var(--ink)] text-[13px] leading-snug group-hover:text-[var(--gold-soft)] transition print:text-slate-900 print:text-xs print:font-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="text-[var(--slate)] text-[10.5px] mt-1 line-clamp-1 print:text-slate-600 print:line-clamp-none print:text-[10px]"><?= htmlspecialchars($row['deskripsi']) ?></div>
                                    </td>
                                    <td class="px-3 py-3.5 align-top overflow-hidden">
                                        <span class="cat-badge" style="background-color:<?= $cat['bg'] ?>;color:<?= $cat['text'] ?>;border-color:<?= $cat['border'] ?>;"><?= htmlspecialchars($row['kategori']) ?></span>
                                        <span class="free-badge mt-1.5"><i class="fa-solid fa-circle-check"></i> Gratis</span>
                                    </td>
                                    <td class="px-3 py-3.5 align-top overflow-hidden text-[var(--ink)] print:text-slate-900">
                                        <div class="font-medium flex items-start gap-1 text-[11px] leading-snug print:text-slate-900"><i class="fa-solid fa-location-dot text-[var(--gold)] print:hidden mt-0.5"></i><span class="line-clamp-2"><?= htmlspecialchars($row['lokasi']) ?></span></div>
                                        <div class="text-[10px] font-mono font-bold text-[var(--gold-soft)] mt-1 flex items-center gap-1 print:text-slate-700 print:font-sans"><i class="fa-solid fa-users text-[var(--gold)] print:hidden"></i><?= $rc ?> / <?= isset($row['kuota']) ? $row['kuota'] : '50' ?> Peserta</div>
                                    </td>
                                    <td class="px-3 py-3.5 align-top overflow-hidden text-center">
                                        <?php if($is_aktif): ?>
                                            <span class="cat-badge inline-flex items-center gap-1 rounded-full" style="background-color:var(--success-bg);color:var(--success);border-color:transparent;"><span class="status-dot bg-[var(--success)] print:hidden"></span><?= $status_text ?></span>
                                        <?php else: ?>
                                            <span class="cat-badge inline-flex items-center gap-1 rounded-full" style="background-color:var(--done-bg);color:var(--done-text);border-color:transparent;"><span class="status-dot bg-[var(--done-text)] print:hidden"></span><?= $status_text ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3.5 align-top overflow-hidden text-center no-print" data-event-id="<?= $eid ?>">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <a href="daftar_event.php?event_id=<?= $eid ?>" title="Daftar Kegiatan" class="daftar-btn flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold transition">
                                                <i class="fa-solid fa-pen-nib"></i> Daftar
                                            </a>
                                            <button type="button" onclick="toggleRegistrants(<?= $eid ?>)" class="peserta-btn flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold transition">
                                                <i class="fa-solid fa-user-group"></i> <span class="peserta-count-label" data-event="<?= $eid ?>"><?= $rc ?></span>
                                            </button>
                                            <button type="button" onclick="toggleLike(<?= $eid ?>, this)" class="like-btn <?= $liked ? 'is-liked' : '' ?> flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold transition" data-liked="<?= $liked ? '1' : '0' ?>">
                                                <span class="like-emoji"><?= $liked ? '🔥' : '🤍' ?></span>
                                                <span class="like-count"><?= $lc ?></span>
                                            </button>
                                            <button type="button" onclick="toggleComments(<?= $eid ?>)" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[var(--line)] bg-[var(--bg-2)] text-[var(--slate)] text-[11px] font-bold">
                                                <i class="fa-regular fa-comment"></i> <span class="comment-count-label" data-event="<?= $eid ?>"><?= count($comments) ?></span>
                                            </button>
                                        </div>
                                    </td>

                                    <?php if ($isAdmin): ?>
                                        <td class="px-3 py-3.5 align-top overflow-hidden text-center no-print">
                                            <div class="flex items-center justify-center gap-1.5">
                                            <a href="edit.php?id=<?= $row['id'] ?>" title="Edit" class="inline-flex items-center justify-center w-7 h-7 text-[var(--gold-soft)] bg-[var(--gold-bg)] hover:bg-[#3E2A16] rounded-lg border border-[var(--gold)]/30 transition">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </a>
                                            <a href="hapus.php?id=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus kegiatan ini?')" class="inline-flex items-center justify-center w-7 h-7 text-[#F0A0A0] bg-[#3A1414] hover:bg-[#4A1A1A] rounded-lg border border-[#5C2020] transition">
                                                <i class="fa-solid fa-trash text-[11px]"></i>
                                            </a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <tr class="no-print">
                                    <td colspan="<?= $colspan ?>" class="px-3 pb-4 pt-0 bg-[var(--surface-2)]/40">
                                        <div id="registrants-panel-<?= $eid ?>" class="registrants-panel hidden mt-2 bg-[var(--bg-2)] rounded-xl border border-[var(--line)] p-3.5 space-y-2 max-h-56 overflow-y-auto">
                                            <?php foreach ($registrants as $p): ?>
                                                <div class="registrant-item bg-[var(--surface)] rounded-lg px-3 py-2 border border-[var(--line-soft)] flex justify-between items-start gap-2">
                                                    <div>
                                                        <span class="text-[11px] font-bold text-[var(--gold-soft)]"><?= htmlspecialchars($p['nama']) ?></span>
                                                        <p class="text-[10px] text-[var(--slate)] mt-0.5"><?= htmlspecialchars($p['nama_campus']) ?> &middot; <?= htmlspecialchars($p['program_studi']) ?> &middot; <?= htmlspecialchars($p['fakultas']) ?></p>
                                                    </div>
                                                    <span class="text-[9px] font-bold uppercase text-[var(--slate)] shrink-0"><?= htmlspecialchars($p['status_user']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($registrants)): ?>
                                                <p class="text-[10px] italic text-[var(--slate-soft)]">Belum ada peserta terdaftar untuk kegiatan ini.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div id="comments-panel-<?= $eid ?>" class="comments-panel hidden mt-2 bg-[var(--bg-2)] rounded-xl border border-[var(--line)] p-3.5 space-y-2.5">
                                            <div class="comment-list space-y-2 max-h-56 overflow-y-auto pr-1">
                                                <?php foreach ($comments as $c): ?>
                                                    <div class="comment-item bg-[var(--surface)] rounded-lg px-3 py-2 border border-[var(--line-soft)]" data-comment-id="<?= $c['id'] ?>">
                                                        <div class="flex justify-between items-start gap-2">
                                                            <span class="text-[10px] font-bold text-[var(--gold-soft)]"><?= htmlspecialchars($c['username']) ?></span>
                                                            <?php if ($isAdmin): ?>
                                                                <button type="button" onclick="deleteComment(<?= $c['id'] ?>, <?= $eid ?>)" class="text-[#F0A0A0] text-[10px] hover:underline">Hapus</button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-[11px] text-[var(--ink)] mt-0.5"><?= nl2br(htmlspecialchars($c['komentar'])) ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (empty($comments)): ?>
                                                    <p class="no-comment-msg text-[10px] italic text-[var(--slate-soft)]">Belum ada komentar.</p>
                                                <?php endif; ?>
                                            </div>
                                            <form onsubmit="return submitComment(event, <?= $eid ?>)" class="flex gap-2">
                                                <input type="text" name="komentar" placeholder="Tulis komentar..." maxlength="500" required class="flex-1 px-3 py-2 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-[11px] text-[var(--ink)] placeholder:text-[var(--slate-soft)] focus:outline-none focus:border-[var(--gold)]">
                                                <button type="submit" class="px-3.5 py-2 bg-[var(--maroon)] hover:bg-[var(--maroon-2)] text-white rounded-lg text-xs font-bold">Kirim</button>
                                            </form>
                                            <p class="comment-error text-[10px] text-[#F0A0A0] hidden"></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? '7' : '6' ?>" class="px-6 py-16 text-center text-[var(--slate-soft)] font-medium bg-[var(--bg-2)]/40 italic">
                                    <i class="fa-regular fa-folder-open text-3xl text-[var(--slate-soft)] block mb-2"></i>
                                    Maaf agenda tidak ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const IS_ADMIN_VIEWER = <?= $isAdmin ? 'true' : 'false' ?>;

        async function toggleLike(eventId, btn) {
            btn.disabled = true;
            try {
                const res = await fetch('like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'event_id=' + encodeURIComponent(eventId)
                });
                const data = await res.json();
                if (data.success) {
                    document.querySelectorAll('.like-btn[onclick*="toggleLike(' + eventId + ',"]').forEach(b => {
                        b.dataset.liked = data.liked ? '1' : '0';
                        b.classList.toggle('is-liked', data.liked);
                        b.querySelector('.like-emoji').textContent = data.liked ? '🔥' : '🤍';
                        b.querySelector('.like-count').textContent = data.count;
                    });
                } else {
                    alert(data.message || 'Gagal memproses like.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
            }
        }

        function toggleComments(eventId) {
            document.querySelectorAll('#comments-panel-' + eventId).forEach(panel => {
                panel.classList.toggle('hidden');
            });
        }

        function toggleRegistrants(eventId) {
            document.querySelectorAll('#registrants-panel-' + eventId).forEach(panel => {
                panel.classList.toggle('hidden');
            });
        }

        async function submitComment(evt, eventId) {
            evt.preventDefault();
            const form = evt.target;
            const input = form.querySelector('input[name="komentar"]');
            const komentar = input.value.trim();
            const panels = document.querySelectorAll('#comments-panel-' + eventId);

            panels.forEach(p => {
                const err = p.querySelector('.comment-error');
                if (err) { err.classList.add('hidden'); err.textContent = ''; }
            });

            if (!komentar) return false;

            try {
                const res = await fetch('comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'event_id=' + encodeURIComponent(eventId) + '&komentar=' + encodeURIComponent(komentar)
                });
                const data = await res.json();

                if (data.success) {
                    const c = data.comment;
                    const deleteBtn = IS_ADMIN_VIEWER
                        ? `<button type="button" onclick="deleteComment(${c.id}, ${eventId})" class="text-[#F0A0A0] text-[10px] hover:underline">Hapus</button>`
                        : '';
                    const html = `
                        <div class="comment-item bg-[var(--bg-2)] rounded-lg px-3 py-2 border border-[var(--line-soft)]" data-comment-id="${c.id}">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-[10px] font-bold text-[var(--gold-soft)]">${c.username}</span>
                                ${deleteBtn}
                            </div>
                            <p class="text-[11px] text-[var(--ink)] mt-0.5">${c.komentar}</p>
                        </div>`;

                    panels.forEach(p => {
                        const list = p.querySelector('.comment-list');
                        const noMsg = list.querySelector('.no-comment-msg');
                        if (noMsg) noMsg.remove();
                        list.insertAdjacentHTML('beforeend', html);
                        list.scrollTop = list.scrollHeight;
                    });

                    document.querySelectorAll('.comment-count-label[data-event="' + eventId + '"]').forEach(el => {
                        el.textContent = (parseInt(el.textContent) || 0) + 1;
                    });

                    form.reset();
                } else {
                    panels.forEach(p => {
                        const err = p.querySelector('.comment-error');
                        if (err) { err.textContent = data.message; err.classList.remove('hidden'); }
                    });
                }
            } catch (err) {
                panels.forEach(p => {
                    const e = p.querySelector('.comment-error');
                    if (e) { e.textContent = 'Terjadi kesalahan jaringan.'; e.classList.remove('hidden'); }
                });
            }
            return false;
        }

        async function deleteComment(commentId, eventId) {
            if (!confirm('Hapus komentar ini?')) return;
            try {
                const res = await fetch('comment_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(commentId)
                });
                const data = await res.json();
                if (data.success) {
                    document.querySelectorAll('[data-comment-id="' + commentId + '"]').forEach(el => el.remove());
                    document.querySelectorAll('.comment-count-label[data-event="' + eventId + '"]').forEach(el => {
                        el.textContent = Math.max(0, (parseInt(el.textContent) || 1) - 1);
                    });
                } else {
                    alert(data.message || 'Gagal menghapus komentar.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            }
        }
    </script>
</body>
</html>
