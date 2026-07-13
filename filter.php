<?php
/**
 * Filter kata tidak pantas — sederhana, berbasis daftar kata (blacklist).
 * Silakan tambah/kurangi daftar $badWords di bawah sesuai kebutuhan kampus kamu.
 *
 * containsBadWords($text) -> true jika teks mengandung kata terlarang.
 */
function containsBadWords($text) {
    $badWords = [
        // Bahasa Indonesia
        'anjing', 'bangsat', 'bajingan', 'kontol', 'memek', 'ngentot',
        'goblok', 'tolol', 'idiot', 'bego', 'kampret', 'brengsek',
        'sialan', 'keparat', 'jancok', 'asu', 'tai', 'taik', 'pantek',
        'kimak', 'babi', 'paok', 'bodoh', 'gilak', 'ajg', 'ppk', 'lol',
        'kntl', 'ngntd',
        // Bahasa Inggris
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'dick', 'pussy',
    ];

    $normalized = strtolower($text);
    // Buang karakter selain huruf/angka/spasi supaya "a.n.j.i.n.g" tetap kena
    $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
    // Normalisasi angka yang sering dipakai gantiin huruf (leetspeak dasar)
    $normalized = strtr($normalized, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't']);

    foreach ($badWords as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $normalized)) {
            return true;
        }
    }
    return false;
}