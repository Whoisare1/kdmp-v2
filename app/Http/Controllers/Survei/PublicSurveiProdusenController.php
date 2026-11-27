<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\ProduksiProdusen;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicSurveiProdusenController extends Controller
{
    /**
     * Daftar komoditas yang disarankan — sesuai sistemsurvey.txt poin 4.
     * Kelompok Tani: hanya komoditas pangan pokok sehari-hari yang disebutkan di .txt.
     * Ekraf: .txt tidak menyebut item spesifik (hanya "Kreativitas Pangan/Benda Desa"),
     *         sehingga tidak ada chip saran — responden isi sendiri.
     */
    private array $saranKomoditas = [
        'Pertanian'  => [
            'Beras', 'Jagung Manis', 'Jagung Pipilan', 'Cabai Rawit Merah', 'Cabai Merah Keriting', 'Cabai Merah Besar',
            'Bawang Merah', 'Bawang Putih', 'Bawang Daun', 'Bawang Prei',
            'Tomat', 'Terong', 'Mentimun', 'Kacang Panjang', 'Buncis', 'Kacang Merah',
            'Kubis', 'Brokoli', 'Pakcoy', 'Sawi', 'Bayam', 'Kangkung', 'Lobak',
            'Wortel', 'Kentang', 'Singkong', 'Ubi Jalar', 'Talas', 'Porang',
            'Kedelai', 'Kacang Hijau', 'Kacang Tanah', 'Sorgum',
            'Melon', 'Semangka', 'Pepaya', 'Pisang', 'Mangga', 'Jeruk', 'Alpukat', 'Durian', 'Rambutan', 'Jambu Kristal', 'Salak',
            'Jamur Tiram', 'Jamur Merang', 'Jamur Kuping',
            'Jahe', 'Kunyit', 'Lengkuas', 'Kencur', 'Serai', 'Daun Kelor', 'Daun Singkong',
            // Hasil Perkebunan (dimasukkan ke Pertanian)
            'Kopi Robusta', 'Kopi Arabika', 'Pucuk Teh', 'Teh Hijau', 'Kakao', 'Kelapa Butir', 'Kelapa Kopyor', 'Kelapa Sawit',
            'Cengkeh', 'Lada', 'Pala', 'Kapulaga', 'Kemiri', 'Kayu Manis', 'Tembakau', 'Karet', 'Tebu', 'Pinang', 'Nilam',
            'Gula Putih', 'Gula Aren', 'Gula Merah'
        ],
        'Peternakan' => [
            'Daging Ayam Broiler', 'Daging Ayam Kampung', 'Daging Bebek Hibrida', 'Daging Bebek Lokal',
            'Daging Sapi', 'Daging Kerbau', 'Daging Kambing', 'Daging Domba',
            'Daging Kelinci', 'Daging Burung Puyuh',
            'Telur Ayam Horen', 'Telur Ayam Omega', 'Telur Ayam Kampung', 'Telur Bebek', 'Telur Puyuh',
            'Susu Sapi', 'Susu Kambing', 'Susu Kerbau', 'Madu Ternak'
        ],
        'Kelautan'   => [
            'Ikan Lele', 'Ikan Nila', 'Ikan Bandeng', 'Garam'
        ],
        'Pangan'     => [],   // Ekraf Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
        'Non Pangan' => [],   // Ekraf Non Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
    ];

    /** Satuan umum */
    private array $satuanList = ['ton', 'kg', 'kwintal'];

    /**
     * Frekuensi panen per tahun (nilai tetap, tanpa tabel database).
     * Sumber data: alursurvei.txt — frekuensi = 365 hari ÷ durasi satu siklus lengkap (tanam/pelihara s.d. panen).
     * Bila acuan berupa rentang (mis. ±3–4 siklus), dipakai batas bawah (siklus lengkap yang pasti tercapai).
     * Komoditas yang di sumbernya "belum dihitung" / bukan siklus tanam-panen sengaja TIDAK dimasukkan,
     * sehingga tidak diisi otomatis.
     */
    private array $frekuensiPanen = [
        // Tanaman Pangan & Palawija
        'padi sawah'          => ['item' => 'Padi sawah',          'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '135–145 hari', 'sumber' => 'Kementan'],
        'jagung pipilan'      => ['item' => 'Jagung pipilan',      'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '80–103 hari',  'sumber' => 'BRMP'],
        'kedelai'             => ['item' => 'Kedelai',             'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => '70–90 hari',   'sumber' => 'Kementan'],
        'kacang tanah'        => ['item' => 'Kacang tanah',        'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–105 hari',  'sumber' => 'BRMP'],
        'kacang hijau'        => ['item' => 'Kacang hijau',        'frekuensi' => 3,   'rentang' => '±3–5',      'durasi' => '55–65 hari',   'sumber' => 'Balitkabi Kementan'],

        // Aneka Umbi-Umbian (Pangan Pokok, Pangan Lokal & Hortikultura Umbi)
        'singkong'            => ['item' => 'Singkong (Ubi kayu)', 'frekuensi' => 1,   'rentang' => '±1',        'durasi' => '7–10 bulan (210–300 hari)', 'sumber' => 'Balitkabi Kementan'],
        'singkong konsumsi'   => ['item' => 'Singkong konsumsi (manis)', 'frekuensi' => 1, 'rentang' => '±1',  'durasi' => '6–9 bulan (180–270 hari)',  'sumber' => 'Balitkabi Kementan'],
        'singkong industri'   => ['item' => 'Singkong industri (tapioka)', 'frekuensi' => 1, 'rentang' => '±1', 'durasi' => '9–12 bulan (270–365 hari)', 'sumber' => 'Balitkabi Kementan'],
        'ubi jalar'           => ['item' => 'Ubi jalar',           'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '90–120 hari',  'sumber' => 'Balitkabi Kementan'],
        'ubi jalar ungu'      => ['item' => 'Ubi jalar ungu (Antin/Ayamurasaki)', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '100–120 hari', 'sumber' => 'Balitkabi Kementan'],
        'ubi cilembu'         => ['item' => 'Ubi Cilembu (ubi madu)', 'frekuensi' => 2, 'rentang' => '±2',      'durasi' => '120–150 hari (4–5 bulan)', 'sumber' => 'Balitkabi Kementan'],
        'ubi jalar kuning'    => ['item' => 'Ubi jalar kuning / putih', 'frekuensi' => 3, 'rentang' => '±3–4',  'durasi' => '90–110 hari',  'sumber' => 'Balitkabi Kementan'],
        'kentang'             => ['item' => 'Kentang sayur (Granola)', 'frekuensi' => 3, 'rentang' => '±3–4',   'durasi' => '90–110 hari (3–3,5 bulan)', 'sumber' => 'Balitsa; BSIP Kementan'],
        'kentang industri'    => ['item' => 'Kentang industri (Atlantik)', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '100–120 hari', 'sumber' => 'Balitsa Kementan'],
        'kentang hitam'       => ['item' => 'Kentang hitam / Kumili', 'frekuensi' => 2, 'rentang' => '±2–3',   'durasi' => '120–135 hari (±4 bulan)',   'sumber' => 'Balitkabi Kementan'],
        'talas bogor'         => ['item' => 'Talas Bogor / Sutra', 'frekuensi' => 1,   'rentang' => '±1',        'durasi' => '6–9 bulan (180–270 hari)',  'sumber' => 'Balitkabi; Ditjen TP'],
        'talas beneng'        => ['item' => 'Talas Beneng',        'frekuensi' => 1,   'rentang' => '±1',        'durasi' => '8–12 bulan (umbi besar)',    'sumber' => 'Balitkabi; Pemprov Banten'],
        'talas jepang'        => ['item' => 'Talas Jepang (Satoimo)', 'frekuensi' => 2, 'rentang' => '±2–3',    'durasi' => '120–150 hari (4–5 bulan)',  'sumber' => 'Balitkabi Kementan'],
        'porang'              => ['item' => 'Porang / Iles-iles',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => '1 musim/tahun (dorman kemarau)', 'sumber' => 'Balitkabi; Ditjen TP'],
        'garut'               => ['item' => 'Garut / Ararut',      'frekuensi' => 1,   'rentang' => '1',         'durasi' => '9–12 bulan (penghasil pati)', 'sumber' => 'Balitkabi Kementan'],
        'ganyong'             => ['item' => 'Ganyong',             'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (dataran rendah)', 'sumber' => 'Balitkabi Kementan'],
        'gembili'             => ['item' => 'Gembili',             'frekuensi' => 1,   'rentang' => '1',         'durasi' => '6–9 bulan (musiman kemarau)', 'sumber' => 'Balitkabi Kementan'],
        'uwi'                 => ['item' => 'Uwi / Ubi kelapa',    'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (musiman kemarau)', 'sumber' => 'Balitkabi Kementan'],
        'suweg'               => ['item' => 'Suweg',               'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (saat daun layu)', 'sumber' => 'Balitkabi Kementan'],
        'gadung'              => ['item' => 'Gadung',              'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–12 bulan',   'sumber' => 'Balitkabi Kementan'],
        'bengkuang'           => ['item' => 'Bengkuang',           'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '105–135 hari (3,5–4,5 bulan)', 'sumber' => 'Balitkabi Kementan'],
        'lobak putih'         => ['item' => 'Lobak putih',         'frekuensi' => 6,   'rentang' => '±5–6',      'durasi' => '45–60 hari (1,5–2 bulan)',  'sumber' => 'Balitsa Kementan'],
        'wortel'              => ['item' => 'Wortel',              'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–100 hari (3–3,5 bulan)', 'sumber' => 'Balitsa Kementan'],
        'bit merah'           => ['item' => 'Bit merah (Beetroot)', 'frekuensi' => 4,  'rentang' => '±4–5',      'durasi' => '60–75 hari (2–2,5 bulan)',  'sumber' => 'Balitsa Kementan'],

        // Bawang-bawangan & Daun Bawang
        'bawang merah'        => ['item' => 'Bawang merah',        'frekuensi' => 3,   'rentang' => '±3–5',      'durasi' => '65–100 hari',  'sumber' => 'BRMP'],
        'bawang putih'        => ['item' => 'Bawang putih',        'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '100–120 hari', 'sumber' => 'Balitsa; Ditjen Hortikultura Kementan'],
        'bawang bombay'       => ['item' => 'Bawang bombay',       'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '110–130 hari', 'sumber' => 'Balitsa Kementan'],
        'bawang daun'         => ['item' => 'Bawang daun / Loncang', 'frekuensi' => 4, 'rentang' => '±4–5',      'durasi' => '60–75 hari',   'sumber' => 'Balitsa; BSIP Kementan'],
        'bawang prei'         => ['item' => 'Bawang prei (Leek)',  'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '75–90 hari',   'sumber' => 'Balitsa; BSIP Kementan'],
        'kucai'               => ['item' => 'Kucai',               'frekuensi' => 10,  'rentang' => '±10–12',    'durasi' => 'Panen pangkas berulang tiap 25–35 hari', 'sumber' => 'Balitsa Kementan'],
        'daun bawang merah'   => ['item' => 'Daun bawang merah',   'frekuensi' => 7,   'rentang' => '±7–8',      'durasi' => '35–45 hari (panen daun muda)', 'sumber' => 'Kementan'],

        // Cabai & Tomat
        'cabai rawit merah'   => ['item' => 'Cabai rawit merah',   'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '75–120 hari',  'sumber' => 'Kementan'],
        'cabai rawit hijau'   => ['item' => 'Cabai rawit hijau',   'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '70–90 hari',   'sumber' => 'Kementan'],
        'cabai merah besar'   => ['item' => 'Cabai merah besar',   'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '90–120 hari',  'sumber' => 'Ditjen Hortikultura Kementan'],
        'cabai merah keriting' => ['item' => 'Cabai merah keriting', 'frekuensi' => 2, 'rentang' => '±2–3',     'durasi' => '90–120 hari',  'sumber' => 'Ditjen Hortikultura Kementan'],
        'cabai hijau besar'   => ['item' => 'Cabai hijau besar',   'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '75–90 hari',   'sumber' => 'Ditjen Hortikultura Kementan'],
        'tomat'               => ['item' => 'Tomat',               'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '75–90 hari',   'sumber' => 'Balitsa Kementan'],

        // Rempah & Bumbu Dapur
        'jahe emprit'         => ['item' => 'Jahe emprit (putih kecil)', 'frekuensi' => 1, 'rentang' => '1',    'durasi' => '8–10 bulan (240–300 hari)', 'sumber' => 'Balittro; BRIN'],
        'jahe gajah'          => ['item' => 'Jahe gajah (putih besar)',  'frekuensi' => 1, 'rentang' => '1',    'durasi' => '8–10 bulan (240–300 hari)', 'sumber' => 'Balittro; BRIN'],
        'jahe merah'          => ['item' => 'Jahe merah',          'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–12 bulan (300–360 hari)', 'sumber' => 'Balittro; BRIN'],
        'kunyit'              => ['item' => 'Kunyit',              'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–12 bulan (240–365 hari)', 'sumber' => 'Balittro; Ditjenbun'],
        'kunyit putih'        => ['item' => 'Kunyit putih',        'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (240–300 hari)', 'sumber' => 'Balittro'],
        'lengkuas'            => ['item' => 'Lengkuas / Laos',     'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–12 bulan (240–365 hari)', 'sumber' => 'Balittro'],
        'kencur'              => ['item' => 'Kencur',              'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (240–300 hari)', 'sumber' => 'Balittro'],
        'temulawak'           => ['item' => 'Temulawak',           'frekuensi' => 1,   'rentang' => '1',         'durasi' => '9–12 bulan (270–365 hari)', 'sumber' => 'Balittro'],
        'serai dapur'         => ['item' => 'Serai dapur',         'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => 'Panen pangkas per 2–3 bulan', 'sumber' => 'Balittro; Ditjenbun'],
        'ketumbar'            => ['item' => 'Ketumbar',            'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–105 hari',  'sumber' => 'Balittro'],
        'lada'                => ['item' => 'Lada / Merica',       'frekuensi' => 1,   'rentang' => '1',         'durasi' => 'Panen tahunan (tanaman tahunan)', 'sumber' => 'Ditjenbun Kementan'],

        // Sayuran Daun & Bunga
        'kubis'               => ['item' => 'Kubis / Kol',         'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '70–90 hari (dataran rendah-sedang)', 'sumber' => 'Balitsa Kementan'],
        'brokoli'             => ['item' => 'Brokoli',             'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '55–70 hari',   'sumber' => 'Balitsa Kementan'],
        'kembang kol'         => ['item' => 'Kembang kol',         'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '50–65 hari',   'sumber' => 'Balitsa Kementan'],
        'pakcoy'              => ['item' => 'Pakcoy / Sawi sendok', 'frekuensi' => 10, 'rentang' => '±10–12',   'durasi' => '25–30 hari',   'sumber' => 'Balitsa; BSIP Kementan'],
        'sawi putih'          => ['item' => 'Sawi putih / Petsai', 'frekuensi' => 6,   'rentang' => '±5–6',      'durasi' => '45–60 hari',   'sumber' => 'Balitsa Kementan'],
        'selada'              => ['item' => 'Selada keriting',     'frekuensi' => 8,   'rentang' => '±8–10',     'durasi' => '30–45 hari',   'sumber' => 'Balitsa Kementan'],
        'seledri'             => ['item' => 'Seledri',             'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '60–75 hari',   'sumber' => 'Balitsa Kementan'],

        // Sayuran Buah & Polong
        'mentimun'            => ['item' => 'Mentimun / Timun',    'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => 'Petik mulai 30–40 hari (siklus 75–80 hari)', 'sumber' => 'Balitsa Kementan'],
        'terong'              => ['item' => 'Terong ungu / hijau', 'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => 'Petik mulai 55–70 hari (siklus 100–120 hari)', 'sumber' => 'Balitsa Kementan'],
        'kacang panjang'      => ['item' => 'Kacang panjang',      'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => 'Petik mulai 40–50 hari (siklus 75–90 hari)', 'sumber' => 'Balitkabi Kementan'],
        'buncis'              => ['item' => 'Buncis',              'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '48–60 hari',   'sumber' => 'Balitsa Kementan'],
        'pare'                => ['item' => 'Pare / Peria',        'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => 'Petik mulai 45–55 hari (siklus 75–90 hari)', 'sumber' => 'Balitsa Kementan'],
        'oyong'               => ['item' => 'Oyong / Gambas',      'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => 'Petik mulai 40–50 hari (siklus 75–90 hari)', 'sumber' => 'Balitsa Kementan'],
        'labu siam'           => ['item' => 'Labu siam / Jipang',  'frekuensi' => 50,  'rentang' => '±50',       'durasi' => 'Panen mingguan (setelah 3–4 bulan)', 'sumber' => 'Balitsa Kementan'],
        'labu kuning'         => ['item' => 'Labu kuning / Waluh', 'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '80–100 hari',  'sumber' => 'Balitsa Kementan'],

        // Serealia Tambahan
        'jagung manis'        => ['item' => 'Jagung manis',        'frekuensi' => 5,   'rentang' => '±4–5',      'durasi' => '65–75 hari',   'sumber' => 'Balitsereal Kementan'],
        'padi gogo'           => ['item' => 'Padi gogo / ladang',  'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '110–135 hari', 'sumber' => 'BB Padi Kementan'],
        'padi ketan'          => ['item' => 'Padi ketan',          'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '120–140 hari', 'sumber' => 'BB Padi Kementan'],
        'sorgum'              => ['item' => 'Sorgum',              'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–105 hari',  'sumber' => 'Balitsereal Kementan'],

        // Sayuran Semusim Lainnya & Jamur
        'bayam'               => ['item' => 'Bayam',               'frekuensi' => 9,   'rentang' => '±9–12',     'durasi' => '25–40 hari',   'sumber' => 'Ditjen Hortikultura; BRMP'],
        'kangkung'            => ['item' => 'Kangkung',            'frekuensi' => 8,   'rentang' => '±8–12',     'durasi' => '27–45 hari',   'sumber' => 'Ditjen Hortikultura; BBPP'],
        'sawi'                => ['item' => 'Sawi',                'frekuensi' => 6,   'rentang' => '±6',        'durasi' => '±60 hari',     'sumber' => 'BBPP Kupang'],
        'jamur tiram'         => ['item' => 'Jamur tiram',         'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–120 hari (masa produktif baglog)', 'sumber' => 'Kementan'],
        'jamur merang'        => ['item' => 'Jamur merang',        'frekuensi' => 15,  'rentang' => '15–18',     'durasi' => '15–20 hari per siklus media jerami', 'sumber' => 'Kementan'],
        'jamur kuping'        => ['item' => 'Jamur kuping',        'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–120 hari per baglog', 'sumber' => 'Kementan'],

        // Buah Hortikultura Semusim & Buah Kebun Rakyat
        'semangka'            => ['item' => 'Semangka',            'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '60–70 hari',   'sumber' => 'Kementan'],
        'melon'               => ['item' => 'Melon',               'frekuensi' => 5,   'rentang' => '±5–6',      'durasi' => '60–70 hari',   'sumber' => 'Balitbu Kementan'],
        'blewah'              => ['item' => 'Blewah',              'frekuensi' => 6,   'rentang' => '±5–6',      'durasi' => '50–60 hari',   'sumber' => 'Balitbu Kementan'],
        'pepaya'              => ['item' => 'Pepaya (California)', 'frekuensi' => 50,  'rentang' => '±50',       'durasi' => 'Panen mingguan saat produktif (mulai 7–9 bln)', 'sumber' => 'Balitbu Kementan'],
        'pisang'              => ['item' => 'Pisang',              'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–12 bulan per tandan produktif', 'sumber' => 'Balitbu Kementan'],
        'nanas'               => ['item' => 'Nanas',               'frekuensi' => 1,   'rentang' => '1',         'durasi' => '12–15 bulan',  'sumber' => 'Balitbu Kementan'],
        'mangga'              => ['item' => 'Mangga (Arumanis/Manalagi)', 'frekuensi' => 1, 'rentang' => '1–2',  'durasi' => '90–120 hari pasca mekar bunga (panen raya Agt–Nov)', 'sumber' => 'Balitbu Tropika Kementan; Ditjen Hortikultura'],
        'jeruk siam'          => ['item' => 'Jeruk siam / keprok', 'frekuensi' => 1,   'rentang' => '1–2',       'durasi' => '8–9 bulan (240–270 hari) pembungaan s/d masak', 'sumber' => 'Balitjestro Kementan'],
        'jeruk nipis'         => ['item' => 'Jeruk nipis / purut', 'frekuensi' => 12,  'rentang' => '12–24',     'durasi' => 'Panen petik periodik per 2–4 minggu', 'sumber' => 'Balitjestro Kementan'],
        'alpukat'             => ['item' => 'Alpukat',             'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '6–7 bulan (180–210 hari) dari bunga, panen raya & susulan', 'sumber' => 'Balitbu Tropika Kementan'],
        'durian'              => ['item' => 'Durian',              'frekuensi' => 1,   'rentang' => '1–2',       'durasi' => '110–140 hari (3,5–4,5 bulan) pasca mekar bunga', 'sumber' => 'Balitbu Tropika Kementan'],
        'rambutan'            => ['item' => 'Rambutan',            'frekuensi' => 1,   'rentang' => '1',         'durasi' => '90–120 hari pasca bunga, panen musiman', 'sumber' => 'Balitbu Tropika Kementan'],
        'jambu kristal'       => ['item' => 'Jambu biji kristal',  'frekuensi' => 10,  'rentang' => '8–12',      'durasi' => 'Panen petik bertahap per 25–35 hari saat produktif', 'sumber' => 'Balitbu Tropika Kementan'],
        'jambu air'           => ['item' => 'Jambu air',           'frekuensi' => 3,   'rentang' => '3–4',       'durasi' => '45–60 hari dari bunga s/d petik, 3–4 panen setahun', 'sumber' => 'Balitbu Tropika Kementan'],
        'salak'               => ['item' => 'Salak',               'frekuensi' => 24,  'rentang' => '12–24',     'durasi' => 'Panen petik rutin 2–4 minggu sekali (2 puncak panen)', 'sumber' => 'Ditjen Hortikultura Kementan'],
        'manggis'             => ['item' => 'Manggis',             'frekuensi' => 1,   'rentang' => '1',         'durasi' => '100–120 hari pasca bunga, panen musiman', 'sumber' => 'Balitbu Tropika Kementan'],
        'belimbing'           => ['item' => 'Belimbing',           'frekuensi' => 3,   'rentang' => '3–4',       'durasi' => '75–90 hari pasca bunga, 3–4 panen per tahun', 'sumber' => 'Ditjen Hortikultura Kementan'],
        'kelengkeng'          => ['item' => 'Kelengkeng',          'frekuensi' => 1,   'rentang' => '1–2',       'durasi' => '120–150 hari (4–5 bulan) dari bunga s/d masak', 'sumber' => 'Balitbu Tropika Kementan'],
        'buah naga'           => ['item' => 'Buah naga',           'frekuensi' => 5,   'rentang' => '4–6',       'durasi' => '30–35 hari pasca bunga mekar (panen berulang Okt–Apr)', 'sumber' => 'Ditjen Hortikultura Kementan'],
        'sukun'               => ['item' => 'Sukun',               'frekuensi' => 2,   'rentang' => '±2',        'durasi' => 'Panen musiman 2 kali setahun', 'sumber' => 'Ditjen Hortikultura Kementan'],
        'nangka'              => ['item' => 'Nangka / Cempedak',   'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '120–150 hari dari bunga hingga masak', 'sumber' => 'Ditjen Hortikultura Kementan'],
        'sirsak'              => ['item' => 'Sirsak',              'frekuensi' => 3,   'rentang' => '±2–3',      'durasi' => '100–120 hari pasca bunga, berbuah bertahap', 'sumber' => 'Balitbu Tropika Kementan'],

        // Sayuran & Polong Tambahan
        'kacang merah'        => ['item' => 'Kacang merah / jago', 'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => '70–85 hari',   'sumber' => 'Balitkabi Kementan'],
        'kacang kapri'        => ['item' => 'Kacang kapri / ercis', 'frekuensi' => 5,  'rentang' => '±5',        'durasi' => '60–70 hari',   'sumber' => 'Balitsa Kementan'],
        'kacang koro'         => ['item' => 'Kacang koro pedang',  'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '120–150 hari', 'sumber' => 'Balitkabi Kementan'],
        'kacang bogor'        => ['item' => 'Kacang bogor',        'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '110–130 hari', 'sumber' => 'Balitkabi Kementan'],
        'kecipir'             => ['item' => 'Kecipir',             'frekuensi' => 20,  'rentang' => '15–25',     'durasi' => 'Petik polong muda tiap 3–5 hari mulai umur 60 hari', 'sumber' => 'Balitsa Kementan'],
        'daun singkong'       => ['item' => 'Daun singkong sayur', 'frekuensi' => 18,  'rentang' => '18–24',     'durasi' => 'Panen pangkas pucuk per 15–20 hari', 'sumber' => 'Balitkabi Kementan'],
        'daun kelor'          => ['item' => 'Daun kelor',          'frekuensi' => 12,  'rentang' => '12–15',     'durasi' => 'Panen pangkas daun tiap 25–30 hari', 'sumber' => 'Balittro Kementan'],
        'daun pepaya'         => ['item' => 'Daun pepaya sayur',   'frekuensi' => 12,  'rentang' => '12–20',     'durasi' => 'Panen petik daun per 15–30 hari', 'sumber' => 'Balitbu Kementan'],
        'kailan'              => ['item' => 'Kailan',              'frekuensi' => 8,   'rentang' => '±8–10',     'durasi' => '35–45 hari',   'sumber' => 'Balitsa Kementan'],
        'paprika'             => ['item' => 'Paprika',             'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–110 hari',  'sumber' => 'Balitsa Kementan'],
        'labu air'            => ['item' => 'Labu air / bligo',    'frekuensi' => 4,   'rentang' => '±4',        'durasi' => '70–85 hari',   'sumber' => 'Balitsa Kementan'],
        'rebung'              => ['item' => 'Rebung bambu',        'frekuensi' => 4,   'rentang' => '±4–6',      'durasi' => 'Panen tunas rebung saat musim hujan (3–4 bln)', 'sumber' => 'Ditjen Hortikultura Kementan'],

        // Tanaman Industri & Rempah (Pertanian)
        'kopi'                => ['item' => 'Kopi Robusta',        'baku' => 'Kopi Robusta',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–11 bulan buah masak (panen raya tahunan)', 'sumber' => 'Puslitkoka; Ditjenbun'],
        'kopi robusta'        => ['item' => 'Kopi Robusta',        'baku' => 'Kopi Robusta',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–11 bulan buah masak (panen raya tahunan)', 'sumber' => 'Puslitkoka; Ditjenbun'],
        'kopi arabika'        => ['item' => 'Kopi Arabika',        'baku' => 'Kopi Arabika',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => '6–9 bulan buah masak (panen raya dataran tinggi)', 'sumber' => 'Puslitkoka; Ditjenbun'],
        'kopi liberika'       => ['item' => 'Kopi Liberika / Excelsa', 'baku' => 'Kopi Liberika', 'frekuensi' => 1, 'rentang' => '1',        'durasi' => '11–12 bulan buah masak (lahan gambut/hutan)', 'sumber' => 'Puslitkoka; Ditjenbun'],
        'teh'                 => ['item' => 'Pucuk teh (kebun produktif)', 'baku' => 'Pucuk Teh', 'frekuensi' => 28, 'rentang' => '24–36',     'durasi' => 'Rotasi pemetikan pucuk per 7–14 hari', 'sumber' => 'PPTK Gambung; Ditjenbun'],
        'pucuk teh'           => ['item' => 'Pucuk teh (daun muda)', 'baku' => 'Pucuk Teh',  'frekuensi' => 28,  'rentang' => '24–36',     'durasi' => 'Rotasi pemetikan pucuk per 7–14 hari', 'sumber' => 'PPTK Gambung; Ditjenbun'],
        'teh hijau'           => ['item' => 'Pucuk teh hijau',     'baku' => 'Teh Hijau',     'frekuensi' => 28,  'rentang' => '24–36',     'durasi' => 'Pemetikan pucuk per 7–14 hari', 'sumber' => 'PPTK Gambung; Ditjenbun'],
        'teh hitam'           => ['item' => 'Pucuk teh hitam',     'baku' => 'Teh Hitam',     'frekuensi' => 28,  'rentang' => '24–36',     'durasi' => 'Pemetikan pucuk per 7–14 hari', 'sumber' => 'PPTK Gambung; Ditjenbun'],
        'teh putih'           => ['item' => 'Teh putih (pecco)',   'baku' => 'Teh Putih',     'frekuensi' => 26,  'rentang' => '24–30',     'durasi' => 'Pemetikan kuncup pucuk pecco per 10–14 hari', 'sumber' => 'PPTK Gambung'],
        'kakao'               => ['item' => 'Kakao (Cokelat)',     'baku' => 'Kakao',         'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '5–6 bulan bunga s/d buah masak (panen raya + sela)', 'sumber' => 'Puslitkoka; Ditjenbun'],
        'kakao lindak'        => ['item' => 'Kakao lindak (Forastero)', 'baku' => 'Kakao',    'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '5–6 bulan bunga s/d masak (curah rakyat)', 'sumber' => 'Puslitkoka'],
        'kakao mulia'         => ['item' => 'Kakao mulia (Criollo)', 'baku' => 'Kakao',      'frekuensi' => 2,   'rentang' => '±2',        'durasi' => '5–6 bulan bunga s/d masak', 'sumber' => 'Puslitkoka'],
        'kelapa'              => ['item' => 'Kelapa butir (kebun rakyat)', 'baku' => 'Kelapa', 'frekuensi' => 12, 'rentang' => '10–12',    'durasi' => 'Panen rutin bulanan (per 25–35 hari per tandan masak)', 'sumber' => 'Balitpalma; Ditjenbun'],
        'kelapa kopyor'       => ['item' => 'Kelapa kopyor',       'baku' => 'Kelapa Kopyor', 'frekuensi' => 12, 'rentang' => '10–12',     'durasi' => 'Panen rutin per 25–35 hari per tandan', 'sumber' => 'Balitpalma; Pemkab Pati'],
        'kelapa dalam'        => ['item' => 'Kelapa dalam (lokal)', 'baku' => 'Kelapa',       'frekuensi' => 10,  'rentang' => '10–12',     'durasi' => 'Panen petik per 30–40 hari', 'sumber' => 'Balitpalma; Ditjenbun'],
        'kelapa hibrida'      => ['item' => 'Kelapa hibrida',      'baku' => 'Kelapa',        'frekuensi' => 12,  'rentang' => '12–14',     'durasi' => 'Panen rutin per 25–30 hari', 'sumber' => 'Balitpalma; Ditjenbun'],
        'kelapa genjah'       => ['item' => 'Kelapa genjah (pandan wangi/entog)', 'baku' => 'Kelapa', 'frekuensi' => 12, 'rentang' => '12–14', 'durasi' => 'Panen rutin per 25–30 hari', 'sumber' => 'Balitpalma; Ditjenbun'],
        'kelapa sawit'        => ['item' => 'Kelapa sawit (TBS)',  'baku' => 'Kelapa Sawit',  'frekuensi' => 24,  'rentang' => '24–30',     'durasi' => 'Panen pusingan potong TBS per 10–14 hari', 'sumber' => 'PPKS; Ditjenbun'],
        'nira kelapa'         => ['item' => 'Nira kelapa (gula kelapa)', 'baku' => 'Gula Kelapa', 'frekuensi' => 300, 'rentang' => '250–365', 'durasi' => 'Penyadapan nira harian (pagi & sore)', 'sumber' => 'Ditjenbun'],
        'cengkeh'             => ['item' => 'Cengkeh',             'baku' => 'Cengkeh',       'frekuensi' => 1,   'rentang' => '1',         'durasi' => 'Panen raya tahunan musim kemarau', 'sumber' => 'Ditjen Perkebunan Kementan'],
        'pala'                => ['item' => 'Pala',                'baku' => 'Pala',          'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => 'Panen raya dan panen susulan (6–7 bulan)', 'sumber' => 'Ditjenbun Kementan'],
        'kapulaga'            => ['item' => 'Kapulaga',            'baku' => 'Kapulaga',      'frekuensi' => 4,   'rentang' => '±4',        'durasi' => 'Panen petik per 3 bulan sekali', 'sumber' => 'Balittro; Ditjenbun'],
        'kemiri'              => ['item' => 'Kemiri',              'baku' => 'Kemiri',        'frekuensi' => 2,   'rentang' => '±2',        'durasi' => 'Panen musiman 2 kali setahun', 'sumber' => 'Ditjenbun Kementan'],
        'vanili'              => ['item' => 'Vanili',              'baku' => 'Vanili',        'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–9 bulan (240–270 hari) penyerbukan s/d panen', 'sumber' => 'Balittro Kementan'],
        'tembakau'            => ['item' => 'Tembakau (rajangan/kering)', 'baku' => 'Tembakau', 'frekuensi' => 1, 'rentang' => '1',         'durasi' => '60–90 hari pertumbuhan s/d panen petik daun (musiman)', 'sumber' => 'Balittas Kementan'],
        'karet'               => ['item' => 'Karet (getah lateks/lump)', 'baku' => 'Karet',   'frekuensi' => 120, 'rentang' => '120–150',   'durasi' => 'Hari sadap efektif pohon produktif selang 1–2 hari', 'sumber' => 'Puslit Karet; Ditjenbun'],
        'tebu'                => ['item' => 'Tebu (giling/manis)', 'baku' => 'Tebu',          'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–12 bulan siklus tebang', 'sumber' => 'P3GI; Ditjenbun'],
        'pinang'              => ['item' => 'Pinang (sirih/betara)', 'baku' => 'Pinang',      'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '8–9 bulan bunga s/d masak (panen 3–4x setahun)', 'sumber' => 'Balitpalma; Ditjenbun'],
        'gambir'              => ['item' => 'Gambir (daun & getah)', 'baku' => 'Gambir',     'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => 'Panen petik daun/ranting muda tiap 3–4 bulan', 'sumber' => 'Balittro Kementan'],
        'nilam'               => ['item' => 'Nilam (minyak atsiri)', 'baku' => 'Nilam',       'frekuensi' => 3,   'rentang' => '±3',        'durasi' => 'Panen pangkas terna tiap 3–4 bulan', 'sumber' => 'Balittro Kementan'],
        'serai wangi'         => ['item' => 'Serai wangi (sitronela)', 'baku' => 'Serai Wangi', 'frekuensi' => 4, 'rentang' => '±4–5',     'durasi' => 'Panen pangkas per 2–3 bulan', 'sumber' => 'Balittro Kementan'],
        'akar wangi'          => ['item' => 'Akar wangi (vetiver)', 'baku' => 'Akar Wangi',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–12 bulan cabut akar panen', 'sumber' => 'Balittro Kementan'],
        'kayu manis'          => ['item' => 'Kayu manis (kulit manis)', 'baku' => 'Kayu Manis', 'frekuensi' => 1, 'rentang' => '1',        'durasi' => 'Rotasi panen kupas kulit kayu tanaman produktif', 'sumber' => 'Balittro; Ditjenbun'],
        'temu kunci'          => ['item' => 'Temu kunci',          'baku' => 'Temu Kunci',    'frekuensi' => 1,   'rentang' => '1',         'durasi' => '8–10 bulan (240–300 hari)', 'sumber' => 'Balittro Kementan'],
        'gula putih'          => ['item' => 'Gula putih (tebu)',   'baku' => 'Gula Putih',    'frekuensi' => 1,   'rentang' => '1',         'durasi' => '10–12 bulan (siklus tebang tebu)', 'sumber' => 'Ditjen Perkebunan Kementan'],
        'gula aren'           => ['item' => 'Gula aren',           'baku' => 'Gula Aren',     'frekuensi' => 300, 'rentang' => '250–365',   'durasi' => 'Penyadapan harian sepanjang tahun', 'sumber' => 'Ditjen Perkebunan Kementan'],

        // Peternakan - Daging Unggas (Ayam)
        'daging ayam broiler' => ['item' => 'Daging ayam broiler', 'frekuensi' => 8,   'rentang' => '±8–10',     'durasi' => '35–42 hari (belum termasuk jeda sanitasi)', 'sumber' => 'Dinas Peternakan Kab. Lebak'],
        'daging ayam kampung' => ['item' => 'Daging ayam kampung', 'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => '60–75 hari (intensif)', 'sumber' => 'Balitnak Kementan'],
        'daging ayam kampung asli' => ['item' => 'Daging ayam kampung asli', 'frekuensi' => 3, 'rentang' => '±3–4', 'durasi' => '90–120 hari (semi-intensif)', 'sumber' => 'Balitnak Kementan'],
        'daging ayam pejantan' => ['item' => 'Daging ayam pejantan', 'frekuensi' => 5, 'rentang' => '±5–6',      'durasi' => '50–60 hari',   'sumber' => 'Ditjen PKH Kementan'],
        'daging ayam petelur afkir' => ['item' => 'Daging ayam petelur afkir', 'frekuensi' => 1, 'rentang' => '1', 'durasi' => 'Afkir akhir siklus bertelur (18–24 bulan)', 'sumber' => 'Ditjen PKH Kementan'],

        // Peternakan - Daging Unggas (Bebek / Itik)
        'daging bebek hibrida' => ['item' => 'Daging bebek hibrida', 'frekuensi' => 6, 'rentang' => '±6–7',     'durasi' => '35–45 hari (intensif)', 'sumber' => 'Balitnak Kementan'],
        'daging bebek lokal'  => ['item' => 'Daging bebek lokal (Mojosari)', 'frekuensi' => 4, 'rentang' => '±4–5', 'durasi' => '60–75 hari', 'sumber' => 'Balitnak Kementan'],
        'daging bebek peking' => ['item' => 'Daging bebek peking', 'frekuensi' => 6,  'rentang' => '±6–7',      'durasi' => '40–50 hari',   'sumber' => 'Balitnak Kementan'],
        'daging entok'        => ['item' => 'Daging entok / itik manila', 'frekuensi' => 4, 'rentang' => '±3–4', 'durasi' => '70–90 hari (2,5–3 bulan)', 'sumber' => 'Balitnak Kementan'],
        'daging bebek afkir'  => ['item' => 'Daging bebek afkir',  'frekuensi' => 1,   'rentang' => '1',         'durasi' => 'Afkir akhir siklus bertelur', 'sumber' => 'Ditjen PKH Kementan'],

        // Peternakan - Ruminansia Besar (Sapi & Kerbau)
        'daging sapi penggemukan' => ['item' => 'Daging sapi penggemukan', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '120–180 hari (khusus penggemukan)', 'sumber' => 'Permentan No. 46 Tahun 2015'],
        'daging sapi lokal'   => ['item' => 'Daging sapi lokal (Bali/PO/Madura)', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '120–180 hari', 'sumber' => 'Ditjen PKH Kementan'],
        'daging sapi perah afkir' => ['item' => 'Daging sapi perah afkir', 'frekuensi' => 1, 'rentang' => '1', 'durasi' => 'Afkir akhir masa laktasi produktif', 'sumber' => 'Ditjen PKH Kementan'],
        'susu sapi perah'     => ['item' => 'Susu sapi perah',     'frekuensi' => 300, 'rentang' => '300–365',   'durasi' => 'Pemerahan harian selama laktasi (±305 hari)', 'sumber' => 'Ditjen PKH Kementan'],
        'daging kerbau penggemukan' => ['item' => 'Daging kerbau penggemukan', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '60–90 hari (intensif) s.d. 150 hari', 'sumber' => 'Balitnak; Ditjen PKH'],
        'daging kerbau afkir' => ['item' => 'Daging kerbau afkir', 'frekuensi' => 1,   'rentang' => '1',         'durasi' => 'Ternak kerbau afkir', 'sumber' => 'Ditjen PKH Kementan'],
        'susu kerbau perah'   => ['item' => 'Susu kerbau perah (Dadih)', 'frekuensi' => 270, 'rentang' => '250–300', 'durasi' => 'Pemerahan harian masa laktasi (270–300 hari)', 'sumber' => 'Balitnak; Ditjen PKH'],

        // Peternakan - Ruminansia Kecil (Kambing & Domba)
        'daging kambing penggemukan' => ['item' => 'Daging kambing penggemukan', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '90–120 hari (penggemukan)', 'sumber' => 'Ditjen PKH Kementan'],
        'daging kambing kacang' => ['item' => 'Daging kambing kacang / lokal', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '90–120 hari', 'sumber' => 'Ditjen PKH Kementan'],
        'daging kambing boer' => ['item' => 'Daging kambing Boer', 'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '90–120 hari',  'sumber' => 'Ditjen PKH Kementan'],
        'susu kambing etawa'  => ['item' => 'Susu kambing etawa (PE)', 'frekuensi' => 210, 'rentang' => '180–210', 'durasi' => 'Pemerahan harian masa laktasi (150–210 hari)', 'sumber' => 'Ditjen PKH Kementan'],
        'daging domba penggemukan' => ['item' => 'Daging domba penggemukan', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '90–120 hari (penggemukan)', 'sumber' => 'Ditjen PKH Kementan'],
        'daging domba garut'  => ['item' => 'Daging domba Garut',  'frekuensi' => 2,   'rentang' => '±2–3',      'durasi' => '90–120 hari',  'sumber' => 'Ditjen PKH Kementan'],
        'daging domba ekor gemuk' => ['item' => 'Daging domba ekor gemuk (DEG)', 'frekuensi' => 2, 'rentang' => '±2–3', 'durasi' => '90–120 hari', 'sumber' => 'Ditjen PKH Kementan'],

        // Peternakan - Aneka Ternak (Puyuh, Kelinci, Lebah Madu, Kuda)
        'daging burung puyuh' => ['item' => 'Daging burung puyuh', 'frekuensi' => 7,   'rentang' => '±7–8',      'durasi' => '35–42 hari (5–6 minggu)', 'sumber' => 'Ditjen PKH Kementan'],
        'telur puyuh'         => ['item' => 'Telur burung puyuh',  'frekuensi' => 300, 'rentang' => '250–300',   'durasi' => 'Produksi harian saat masa bertelur', 'sumber' => 'Ditjen PKH Kementan'],
        'daging kelinci'      => ['item' => 'Daging kelinci pedaging', 'frekuensi' => 4, 'rentang' => '±4–5',    'durasi' => '60–90 hari (2–3 bulan)', 'sumber' => 'Balitnak Kementan'],
        'madu ternak'         => ['item' => 'Madu lebah ternak',   'frekuensi' => 8,   'rentang' => '±6–8',      'durasi' => 'Panen per 15–30 hari saat musim bunga', 'sumber' => 'Ditjenbun Kementan'],
        'madu klanceng'       => ['item' => 'Madu klanceng (Trigona)', 'frekuensi' => 4, 'rentang' => '±4',     'durasi' => 'Panen per 2–3 bulan', 'sumber' => 'Ditjenbun Kementan'],
        'susu kuda liar'      => ['item' => 'Susu kuda liar (Sumbawa)', 'frekuensi' => 180, 'rentang' => '150–180', 'durasi' => 'Pemerahan harian saat laktasi', 'sumber' => 'Ditjen PKH Kementan'],

        // Peternakan - Telur
        'telur ayam horen'    => ['item' => 'Telur ayam horen (ras)', 'frekuensi' => 365, 'rentang' => '300–365', 'durasi' => 'Panen harian (masa produktif 12–18 bulan)', 'sumber' => 'Ditjen PKH Kementan'],
        'telur ayam omega'    => ['item' => 'Telur ayam omega',    'frekuensi' => 365, 'rentang' => '300–365',   'durasi' => 'Panen harian (masa produktif 12–18 bulan)', 'sumber' => 'Ditjen PKH Kementan'],
        'telur ayam kampung'  => ['item' => 'Telur ayam kampung',  'frekuensi' => 150, 'rentang' => '100–180',   'durasi' => 'Periodik/harian (47–146 butir/ekor/tahun)', 'sumber' => 'Balitnak Kementan'],
        'telur ayam arab'     => ['item' => 'Telur ayam Arab',     'frekuensi' => 250, 'rentang' => '200–250',   'durasi' => 'Produksi harian saat bertelur', 'sumber' => 'Balitnak Kementan'],
        'telur bebek'         => ['item' => 'Telur bebek (itik)',  'frekuensi' => 250, 'rentang' => '200–300',   'durasi' => 'Produksi harian saat masa bertelur', 'sumber' => 'Balitnak Kementan'],
        'telur asin'          => ['item' => 'Telur asin (olahan)', 'frekuensi' => 50,  'rentang' => '±50',       'durasi' => 'Produksi mingguan sepanjang tahun', 'sumber' => 'Ditjen PKH Kementan'],

        // Perikanan
        'ikan lele'           => ['item' => 'Ikan lele',           'frekuensi' => 4,   'rentang' => '±4–5',      'durasi' => '60–90 hari (pembesaran konsumsi)', 'sumber' => 'Ditjen Perikanan Budidaya KKP'],
        'ikan nila'           => ['item' => 'Ikan nila',           'frekuensi' => 3,   'rentang' => '±3–4',      'durasi' => '90–120 hari (pembesaran konsumsi)', 'sumber' => 'Ditjen Perikanan Budidaya KKP'],
    ];

    /** Nama lain yang dikenali → kunci pada $frekuensiPanen. */
    private array $aliasFrekuensi = [
        'padi'                => 'padi sawah',
        'beras'               => 'padi sawah',
        'jagung'              => 'jagung pipilan',
        'kacang ijo'          => 'kacang hijau',

        // Aneka Umbi-Umbian
        'ubi kayu'            => 'singkong',
        'singkong manis'      => 'singkong konsumsi',
        'singkong manggu'     => 'singkong konsumsi',
        'singkong mentega'    => 'singkong konsumsi',
        'singkong gajah'      => 'singkong konsumsi',
        'singkong racun'      => 'singkong industri',
        'singkong tapioka'    => 'singkong industri',
        'tapioka'             => 'singkong industri',
        'ubi'                 => 'ubi jalar',
        'ubi madu'            => 'ubi cilembu',
        'cilembu'             => 'ubi cilembu',
        'ubi ungu'            => 'ubi jalar ungu',
        'ubi jalar madu'      => 'ubi cilembu',
        'ubi kuning'          => 'ubi jalar kuning',
        'ubi putih'           => 'ubi jalar kuning',
        'kentang'             => 'kentang',
        'kentang granola'     => 'kentang',
        'kentang sayur'       => 'kentang',
        'kentang atlantik'    => 'kentang industri',
        'kentang jawa'        => 'kentang hitam',
        'kumili'              => 'kentang hitam',
        'talas'               => 'talas bogor',
        'talas sutra'         => 'talas bogor',
        'kimpul'              => 'talas bogor',
        'keladi'              => 'talas bogor',
        'satoimo'             => 'talas jepang',
        'iles iles'           => 'porang',
        'iles-iles'           => 'porang',
        'ilesiles'            => 'porang',
        'ararut'              => 'garut',
        'ubi garut'           => 'garut',
        'sagu garut'          => 'garut',
        'ubi kelapa'          => 'uwi',
        'uwi ungu'            => 'uwi',
        'uwi beras'           => 'uwi',
        'bengkoang'           => 'bengkuang',
        'lobak'               => 'lobak putih',
        'bit'                 => 'bit merah',
        'beet'                => 'bit merah',
        'beetroot'            => 'bit merah',

        // Daun Bawang
        'daun bawang'         => 'bawang daun',
        'loncang'             => 'bawang daun',
        'bawang bakung'       => 'bawang daun',
        'daun loncang'        => 'bawang daun',
        'prei'                => 'bawang prei',
        'leek'                => 'bawang prei',
        'daun bawang prei'    => 'bawang prei',
        'bawang kucai'        => 'kucai',
        'daun kucai'          => 'kucai',
        'daun bawang merah'   => 'daun bawang merah',

        // Bawang
        'bawangputih'         => 'bawang putih',
        'bombay'              => 'bawang bombay',

        // Cabai (Master 'cabai'/'cabe' jatuh ke cabai rawit merah sebagai yang paling umum di konsumsi harian)
        'cabai'               => 'cabai rawit merah',
        'cabe'                => 'cabai rawit merah',
        'cabai rawit'         => 'cabai rawit merah',
        'cabe rawit'          => 'cabai rawit merah',
        'cabe rawit merah'    => 'cabai rawit merah',
        'cabe rawit hijau'    => 'cabai rawit hijau',
        'cabai merah'         => 'cabai merah besar',
        'cabe merah'          => 'cabai merah besar',
        'cabe merah besar'    => 'cabai merah besar',
        'cabai keriting'      => 'cabai merah keriting',
        'cabe keriting'       => 'cabai merah keriting',
        'cabe merah keriting' => 'cabai merah keriting',
        'cabe hijau'          => 'cabai hijau besar',
        'cabe hijau besar'    => 'cabai hijau besar',

        // Rempah & Bumbu
        'jahe'                => 'jahe emprit',
        'jahe putih'          => 'jahe emprit',
        'jahe putih kecil'    => 'jahe emprit',
        'jahe putih besar'    => 'jahe gajah',
        'kunir'               => 'kunyit',
        'laos'                => 'lengkuas',
        'temu lawak'          => 'temulawak',
        'serai'               => 'serai dapur',
        'sereh'               => 'serai dapur',
        'sere'                => 'serai dapur',
        'merica'              => 'lada',

        // Gula & Pemanis
        'tebu'                => 'gula putih',
        'gula pasir'          => 'gula putih',
        'gula'                => 'gula putih',
        'gula merah'          => 'gula aren',
        'gula jawa'           => 'gula aren',
        'gula kelapa'         => 'gula aren',

        // Daging Ayam (Master 'daging ayam' / 'ayam' jatuh ke Broiler sebagai yang paling umum dikenal masyarakat)
        'daging ayam'         => 'daging ayam broiler',
        'ayam'                => 'daging ayam broiler',
        'ayam broiler'        => 'daging ayam broiler',
        'ayam ras'            => 'daging ayam broiler',
        'ayam negeri'         => 'daging ayam broiler',
        'ayam potong'         => 'daging ayam broiler',
        'ayam kampung'        => 'daging ayam kampung',
        'ayam pejantan'       => 'daging ayam pejantan',
        'ayam petelur afkir'  => 'daging ayam petelur afkir',
        'ayam afkir'          => 'daging ayam petelur afkir',

        // Daging Bebek / Itik (Master 'daging bebek' / 'bebek' jatuh ke Bebek Hibrida sebagai pedaging lumrah)
        'daging bebek'        => 'daging bebek hibrida',
        'bebek'               => 'daging bebek hibrida',
        'bebek pedaging'      => 'daging bebek hibrida',
        'daging itik'         => 'daging bebek hibrida',
        'itik'                => 'daging bebek hibrida',
        'bebek hibrida'       => 'daging bebek hibrida',
        'bebek lokal'         => 'daging bebek lokal',
        'bebek mojosari'      => 'daging bebek lokal',
        'bebek jawa'          => 'daging bebek lokal',
        'itik manila'         => 'daging bebek lokal',
        'entok'               => 'daging bebek lokal',
        'mentok'              => 'daging bebek lokal',
        'bebek peking'        => 'daging bebek peking',
        'bebek afkir'         => 'daging bebek afkir',
        'itik afkir'          => 'daging bebek afkir',

        // Sapi, Kerbau, Kambing, Domba
        'daging sapi'         => 'daging sapi penggemukan',
        'sapi'                => 'daging sapi penggemukan',
        'sapi potong'         => 'daging sapi penggemukan',
        'sapi penggemukan'    => 'daging sapi penggemukan',
        'sapi bali'           => 'daging sapi lokal',
        'sapi po'             => 'daging sapi lokal',
        'sapi ongole'         => 'daging sapi lokal',
        'sapi madura'         => 'daging sapi lokal',
        'sapi limousin'       => 'daging sapi penggemukan',
        'sapi simmental'      => 'daging sapi penggemukan',
        'sapi perah afkir'    => 'daging sapi perah afkir',
        'susu sapi'           => 'susu sapi perah',
        'daging kerbau'       => 'daging kerbau penggemukan',
        'kerbau'              => 'daging kerbau penggemukan',
        'kerbau potong'       => 'daging kerbau penggemukan',
        'kerbau rawa'         => 'daging kerbau penggemukan',
        'kerbau lumpur'       => 'daging kerbau penggemukan',
        'kerbau toraja'       => 'daging kerbau penggemukan',
        'kerbau sumbawa'      => 'daging kerbau penggemukan',
        'kerbau afkir'        => 'daging kerbau afkir',
        'susu kerbau'         => 'susu kerbau perah',
        'dadih'               => 'susu kerbau perah',
        'daging kambing'      => 'daging kambing penggemukan',
        'kambing'             => 'daging kambing penggemukan',
        'kambing potong'      => 'daging kambing penggemukan',
        'kambing penggemukan' => 'daging kambing penggemukan',
        'kambing kacang'      => 'daging kambing kacang',
        'kambing jawa'        => 'daging kambing kacang',
        'kambing jawarandu'   => 'daging kambing kacang',
        'kambing boer'        => 'daging kambing boer',
        'kambing pe'          => 'daging kambing penggemukan',
        'kambing etawa'       => 'susu kambing etawa',
        'susu kambing'        => 'susu kambing etawa',
        'daging domba'        => 'daging domba penggemukan',
        'domba'               => 'daging domba penggemukan',
        'domba potong'        => 'daging domba penggemukan',
        'domba penggemukan'   => 'daging domba penggemukan',
        'domba garut'         => 'daging domba garut',
        'domba ekor gemuk'    => 'daging domba ekor gemuk',
        'domba deg'           => 'daging domba ekor gemuk',
        'domba ekor tipis'    => 'daging domba penggemukan',
        'domba texel'         => 'daging domba penggemukan',
        'domba merino'        => 'daging domba penggemukan',
        'biri biri'           => 'daging domba penggemukan',
        'biri-biri'           => 'daging domba penggemukan',

        // Aneka Ternak (Puyuh, Kelinci, Kuda, Madu)
        'burung puyuh'        => 'daging burung puyuh',
        'puyuh'               => 'daging burung puyuh',
        'daging puyuh'        => 'daging burung puyuh',
        'puyuh pedaging'      => 'daging burung puyuh',
        'puyuh petelur'       => 'telur puyuh',
        'telur burung puyuh'  => 'telur puyuh',
        'kelinci'             => 'daging kelinci',
        'kelinci pedaging'    => 'daging kelinci',
        'kelinci potong'      => 'daging kelinci',
        'daging kelinci potong' => 'daging kelinci',
        'kuda'                => 'susu kuda liar',
        'susu kuda'           => 'susu kuda liar',
        'madu'                => 'madu ternak',
        'madu lebah'          => 'madu ternak',
        'madu hutan'          => 'madu ternak',
        'madu murni'          => 'madu ternak',
        'madu klanceng'       => 'madu klanceng',
        'madu trigona'        => 'madu klanceng',
        'madu kelulut'        => 'madu klanceng',
        'klanceng'            => 'madu klanceng',

        // Telur
        'telur horen'         => 'telur ayam horen',
        'telur ayam horn'     => 'telur ayam horen',
        'telur horn'          => 'telur ayam horen',
        'telur ayam ras'      => 'telur ayam horen',
        'telur ras'           => 'telur ayam horen',
        'telur ayam negeri'   => 'telur ayam horen',
        'telur negeri'        => 'telur ayam horen',
        'telur ayam'          => 'telur ayam horen',
        'telur'               => 'telur ayam horen',
        'telur omega'         => 'telur ayam omega',
        'telur ayam omega 3'  => 'telur ayam omega',
        'telur omega 3'       => 'telur ayam omega',
        'telur ayam lokal'    => 'telur ayam kampung',
        'telur itik'          => 'telur bebek',

        // Ikan
        'lele'                => 'ikan lele',
        'nila'                => 'ikan nila',

        // Sayuran Daun & Bunga
        'kol'                 => 'kubis',
        'kobis'               => 'kubis',
        'kubis kol'           => 'kubis',
        'bunga kol'           => 'kembang kol',
        'sawi sendok'         => 'pakcoy',
        'pokcoy'              => 'pakcoy',
        'petsai'              => 'sawi putih',
        'pecai'               => 'sawi putih',
        'pechay'              => 'sawi putih',
        'selada keriting'     => 'selada',
        'daun seledri'        => 'seledri',

        // Sayuran Buah & Polong
        'timun'               => 'mentimun',
        'timun mentimun'      => 'mentimun',
        'terung'              => 'terong',
        'terong ungu'         => 'terong',
        'terong hijau'        => 'terong',
        'terong lalap'        => 'terong',
        'kacang lanjaran'     => 'kacang panjang',
        'buncis tegak'        => 'buncis',
        'baby buncis'         => 'buncis',
        'peria'               => 'pare',
        'paria'               => 'pare',
        'gambas'              => 'oyong',
        'jipang'              => 'labu siam',
        'waluh'               => 'labu kuning',
        'labu manis'          => 'labu kuning',

        // Serealia Tambahan
        'jagung manis pipil'  => 'jagung manis',
        'sweet corn'          => 'jagung manis',
        'padi gogo rancah'    => 'padi gogo',
        'padi ladang'         => 'padi gogo',
        'padi darat'          => 'padi gogo',
        'ketan'               => 'padi ketan',
        'beras ketan'         => 'padi ketan',
        'ketan putih'         => 'padi ketan',
        'ketan hitam'         => 'padi ketan',
        'sorghum'             => 'sorgum',
        'cantel'              => 'sorgum',

        // Jamur
        'jamur tiram putih'   => 'jamur tiram',
        'merang'              => 'jamur merang',
        'jamur padi'          => 'jamur merang',
        'kuping'              => 'jamur kuping',

        // Buah Hortikultura Semusim & Tahunan
        'semangka merah'      => 'semangka',
        'semangka kuning'     => 'semangka',
        'semangka non biji'   => 'semangka',
        'melon hijau'         => 'melon',
        'melon oranye'        => 'melon',
        'timun suri'          => 'blewah',
        'pepaya california'   => 'pepaya',
        'pepaya calina'       => 'pepaya',
        'pepaya bangkok'      => 'pepaya',
        'pisang kepok'        => 'pisang',
        'pisang raja'         => 'pisang',
        'pisang cavendish'    => 'pisang',
        'pisang ambon'        => 'pisang',
        'pisang barangan'     => 'pisang',
        'pisang mas'          => 'pisang',
        'nanas madu'          => 'nanas',
        'nanas subang'        => 'nanas',
        'nanas palembang'     => 'nanas',

        // Aneka Buah Kebun Rakyat
        'mangga harum manis'  => 'mangga',
        'mangga arumanis'     => 'mangga',
        'mangga manalagi'     => 'mangga',
        'mangga gedong gincu' => 'mangga',
        'mangga gedong'       => 'mangga',
        'mangga indramayu'    => 'mangga',
        'mangga cengkir'      => 'mangga',
        'mangga golek'        => 'mangga',
        'mangga apel'         => 'mangga',
        'buah mangga'         => 'mangga',
        'jeruk'               => 'jeruk siam',
        'jeruk manis'         => 'jeruk siam',
        'jeruk keprok'        => 'jeruk siam',
        'jeruk pontianak'     => 'jeruk siam',
        'jeruk medan'         => 'jeruk siam',
        'jeruk bali'          => 'jeruk siam',
        'jeruk pamelo'        => 'jeruk siam',
        'jeruk purut'         => 'jeruk nipis',
        'jeruk limau'         => 'jeruk nipis',
        'jeruk sambal'        => 'jeruk nipis',
        'lemon'               => 'jeruk nipis',
        'avokad'              => 'alpukat',
        'alpukat mentega'     => 'alpukat',
        'alpukat miki'        => 'alpukat',
        'alpukat aligator'    => 'alpukat',
        'duren'               => 'durian',
        'durian montong'      => 'durian',
        'durian musang king'  => 'durian',
        'durian bawor'        => 'durian',
        'durian petruk'       => 'durian',
        'durian lokal'        => 'durian',
        'rambutan binjai'     => 'rambutan',
        'rambutan rapiah'     => 'rambutan',
        'rambutan aceh'       => 'rambutan',
        'jambu'               => 'jambu kristal',
        'jambu biji'          => 'jambu kristal',
        'jambu klutuk'        => 'jambu kristal',
        'jambu getas merah'   => 'jambu kristal',
        'jambu merah'         => 'jambu kristal',
        'jambu air citra'     => 'jambu air',
        'jambu madu deli'     => 'jambu air',
        'jambu madu'          => 'jambu air',
        'jambu cincalo'       => 'jambu air',
        'salak pondoh'        => 'salak',
        'salak bali'          => 'salak',
        'salak madu'          => 'salak',
        'buah manggis'        => 'manggis',
        'belimbing dewa'      => 'belimbing',
        'belimbing madu'      => 'belimbing',
        'belimbing manis'     => 'belimbing',
        'lengkeng'            => 'kelengkeng',
        'kelengkeng kateki'   => 'kelengkeng',
        'kelengkeng itoh'     => 'kelengkeng',
        'naga merah'          => 'buah naga',
        'naga putih'          => 'buah naga',
        'dragon fruit'        => 'buah naga',
        'buah sukun'          => 'sukun',
        'nangka madu'         => 'nangka',
        'nangka sayur'        => 'nangka',
        'gori'                => 'nangka',
        'tewel'               => 'nangka',
        'cempedak'            => 'nangka',
        'sirsak ratu'         => 'sirsak',
        'nangka belanda'      => 'sirsak',

        // Sayuran, Daun & Polong Tambahan
        'kacang jago'         => 'kacang merah',
        'kacang kidney'       => 'kacang merah',
        'kapri'               => 'kacang kapri',
        'kacang ercis'        => 'kacang kapri',
        'ercis'               => 'kacang kapri',
        'koro pedang'         => 'kacang koro',
        'koro'                => 'kacang koro',
        'kacang bambara'      => 'kacang bogor',
        'cipir'               => 'kecipir',
        'kacang botol'        => 'kecipir',
        'pucuk ubi'           => 'daun singkong',
        'daun ubi kayu'       => 'daun singkong',
        'kelor'               => 'daun kelor',
        'daun kates'          => 'daun pepaya',
        'baby kailan'         => 'kailan',
        'paprika merah'       => 'paprika',
        'paprika hijau'       => 'paprika',
        'paprika kuning'      => 'paprika',
        'bligo'               => 'labu air',
        'kundur'              => 'labu air',
        'labu botol'          => 'labu air',
        'rebung bambu'        => 'rebung',
        'bung'                => 'rebung',

        // Tanaman Rempah & Industri Tambahan (Kopi, Teh, Kakao/Coklat, Kelapa, Tembakau, Karet, Tebu, dsb)
        'kopi'                => 'kopi robusta',
        'kopi luwak'          => 'kopi robusta',
        'biji kopi'           => 'kopi robusta',
        'kopi petik merah'    => 'kopi robusta',
        'kopi bubuk'          => 'kopi robusta',
        'kopi excelsa'        => 'kopi liberika',
        'teh'                 => 'pucuk teh',
        'daun teh'            => 'pucuk teh',
        'teh oolong'          => 'pucuk teh',
        'white tea'           => 'teh putih',
        'kebun teh'           => 'pucuk teh',
        'coklat'              => 'kakao',
        'cokelat'             => 'kakao',
        'biji coklat'         => 'kakao',
        'biji cokelat'        => 'kakao',
        'buah coklat'         => 'kakao',
        'buah cokelat'        => 'kakao',
        'pohon coklat'        => 'kakao',
        'kakao edel'          => 'kakao mulia',
        'criollo'             => 'kakao mulia',
        'forastero'           => 'kakao lindak',
        'kakao hibrida'       => 'kakao',
        'kelapa butir'        => 'kelapa',
        'kelapa kopyor'       => 'kelapa kopyor',
        'kopyor'              => 'kelapa kopyor',
        'kelapa tua'          => 'kelapa',
        'kelapa muda'         => 'kelapa',
        'degan'               => 'kelapa',
        'kelapa pandan wangi' => 'kelapa genjah',
        'kelapa entog'        => 'kelapa genjah',
        'kelapa sawit'        => 'kelapa sawit',
        'sawit'               => 'kelapa sawit',
        'tbs sawit'           => 'kelapa sawit',
        'tbs'                 => 'kelapa sawit',
        'buah sawit'          => 'kelapa sawit',
        'badeg'               => 'nira kelapa',
        'tembakau'            => 'tembakau',
        'mbako'               => 'tembakau',
        'daun tembakau'       => 'tembakau',
        'tembakau rajangan'   => 'tembakau',
        'tembakau madura'     => 'tembakau',
        'tembakau virginia'   => 'tembakau',
        'tembakau srintil'     => 'tembakau',
        'karet'               => 'karet',
        'getah karet'         => 'karet',
        'lateks'              => 'karet',
        'lump'                => 'karet',
        'koagulum'            => 'karet',
        'tebu'                => 'tebu',
        'tebu giling'         => 'tebu',
        'tebu manis'          => 'tebu',
        'pinang'              => 'pinang',
        'buah pinang'         => 'pinang',
        'pinang betara'       => 'pinang',
        'pinang sirih'        => 'pinang',
        'gambir'              => 'gambir',
        'daun gambir'         => 'gambir',
        'nilam'               => 'nilam',
        'daun nilam'          => 'nilam',
        'sereh wangi'         => 'serai wangi',
        'sitronela'           => 'serai wangi',
        'akar wangi'          => 'akar wangi',
        'vetiver'             => 'akar wangi',
        'kayu manis'          => 'kayu manis',
        'kulit manis'         => 'kayu manis',
        'cinnamon'            => 'kayu manis',
        'bunga cengkeh'       => 'cengkeh',
        'cengkih'             => 'cengkeh',
        'biji pala'           => 'pala',
        'fuli pala'           => 'pala',
        'kapol'               => 'kapulaga',
        'kapulaga jawa'       => 'kapulaga',
        'biji kemiri'         => 'kemiri',
        'muncang'             => 'kemiri',
        'vanila'              => 'vanili',
        'polong vanili'       => 'vanili',
        'kunci'               => 'temu kunci',
    ];

    /** Peta nama (kanonik + alias) → data frekuensi & kategori otomatis, untuk dipakai di JavaScript. */
    private function petaFrekuensi(): array
    {
        $peta = $this->frekuensiPanen;
        foreach ($this->aliasFrekuensi as $alias => $kunci) {
            if (isset($this->frekuensiPanen[$kunci])) {
                $peta[$alias] = $this->frekuensiPanen[$kunci];
            }
        }

        // Tentukan kategori & sub-kategori otomatis untuk setiap komoditas
        foreach ($peta as $nama => &$info) {
            $n = strtolower($nama);
            if (str_starts_with($n, 'daging') || str_starts_with($n, 'ayam') || str_starts_with($n, 'bebek')
                || str_starts_with($n, 'itik') || str_starts_with($n, 'entok') || str_starts_with($n, 'mentok')
                || str_starts_with($n, 'sapi') || str_starts_with($n, 'kerbau') || str_starts_with($n, 'kambing')
                || str_starts_with($n, 'domba') || str_starts_with($n, 'biri') || str_starts_with($n, 'kelinci')
                || str_starts_with($n, 'puyuh') || str_starts_with($n, 'telur') || str_starts_with($n, 'susu')
                || str_starts_with($n, 'madu') || $n === 'dadih' || $n === 'klanceng') {
                $info['kategori'] = 'Kelompok Tani';
                $info['sub']      = 'Peternakan';
            } elseif (str_starts_with($n, 'ikan') || str_starts_with($n, 'udang') || str_starts_with($n, 'cumi')
                || str_starts_with($n, 'lele') || str_starts_with($n, 'nila') || str_starts_with($n, 'gurame')
                || str_starts_with($n, 'bandeng') || str_starts_with($n, 'patin') || $n === 'garam') {
                $info['kategori'] = 'Kelompok Tani';
                $info['sub']      = 'Kelautan';
            } elseif (str_starts_with($n, 'keripik') || str_starts_with($n, 'jamu') || str_starts_with($n, 'anyaman') || str_starts_with($n, 'batik')) {
                $info['kategori'] = 'Ekraf';
                $info['sub']      = (str_starts_with($n, 'anyaman') || str_starts_with($n, 'batik')) ? 'Non Pangan' : 'Pangan';
            } else {
                $info['kategori'] = 'Kelompok Tani';
                $info['sub']      = 'Pertanian';
            }
        }
        unset($info);

        return $peta;
    }

    /**
     * Tampilkan halaman survei publik Produsen.
     */
    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_produsen', $token)
            ->with(['wilayah'])
            ->firstOrFail();

        // Validasi periode: hanya boleh diakses bulan aktif
        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei Produsen ini sudah kedaluwarsa. Survei hanya berlaku untuk periode '
                . $sesi->bulan . '/' . $sesi->tahun . '.');
        }

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('survei.public.produsen', [
            'sesi'           => $sesi,
            'token'          => $token,
            'bulans'         => $bulans,
            'saranKomoditas' => $this->saranKomoditas,
            'satuanList'     => $this->satuanList,
            'frekuensiPanen' => $this->petaFrekuensi(),
        ]);
    }

    /**
     * Simpan batch komoditas dalam satu POST.
     * Payload: nama_responden + komoditas[] (array of items)
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei ini sudah kedaluwarsa.');
        }

        $request->validate([
            'nama_responden'    => 'required|string|max:100',
            'komoditas'         => 'required|array|min:1',
            'komoditas.*.nama_komoditas'  => 'required|string|max:100',
            'komoditas.*.kategori'        => 'required|in:Kelompok Tani,Ekraf',
            'komoditas.*.sub_kategori'    => 'nullable|string|max:50',
            'komoditas.*.jumlah_produksi' => 'nullable|numeric|min:0',
            'komoditas.*.satuan'          => 'nullable|string|max:30',
            'komoditas.*.harga'           => 'nullable|numeric|min:0',
            'komoditas.*.tipe_harga'      => 'nullable|in:Satuan,Total',
            'komoditas.*.bulan_produksi'  => 'nullable|integer|min:1|max:12',
            'komoditas.*.keterangan'      => 'nullable|string|max:500',
        ], [
            'komoditas.required'                   => 'Harap tambah minimal 1 komoditas ke daftar.',
            'komoditas.*.nama_komoditas.required'  => 'Nama komoditas tidak boleh kosong.',
        ]);

        // Bersihkan nama responden dari titik (voice-to-text)
        $namaResponden = ucwords(strtolower(trim(str_replace('.', '', $request->nama_responden))));

        $rows = [];
        $now  = now();
        $peta = $this->petaFrekuensi();

        foreach ($request->komoditas as $item) {
            $namaClean = ucwords(strtolower(trim(str_replace('.', '', $item['nama_komoditas']))));
            $kmdLower  = strtolower($namaClean);

            $kategori    = $item['kategori'];
            $subKategori = $item['sub_kategori'] ?? null;

            // Auto-kategorisasi otomatis di backend jika ada ketidaksesuaian input
            if (isset($peta[$kmdLower]) && !empty($peta[$kmdLower]['sub'])) {
                $subKategori = $peta[$kmdLower]['sub'];
                $kategori    = $peta[$kmdLower]['kategori'] ?? $kategori;
            } else {
                foreach ($peta as $k => $info) {
                    if (strlen($k) >= 3 && !empty($info['sub'])) {
                        if (str_starts_with($kmdLower, $k . ' ') || str_ends_with($kmdLower, ' ' . $k) || str_contains($kmdLower, ' ' . $k . ' ')) {
                            $subKategori = $info['sub'];
                            $kategori    = $info['kategori'] ?? $kategori;
                            break;
                        }
                    }
                }
            }

            $rows[] = [
                'id_sesi'         => $sesi->id,
                'id_wilayah'      => $sesi->id_wilayah,
                'nama_responden'  => $namaResponden,
                'kategori'        => $kategori,
                'sub_kategori'    => $subKategori,
                'nama_komoditas'  => $namaClean,
                'jumlah_produksi' => filled($item['jumlah_produksi']) ? $item['jumlah_produksi'] : null,
                'satuan'          => $item['satuan'] ?? null,
                'harga'           => filled($item['harga'] ?? null) ? $item['harga'] : null,
                'tipe_harga'      => filled($item['harga'] ?? null) ? ($item['tipe_harga'] ?? 'Satuan') : 'Satuan',
                'bulan_produksi'  => filled($item['bulan_produksi'] ?? null) ? (int) $item['bulan_produksi'] : (int) $sesi->bulan,
                'tahun_produksi'  => $sesi->tahun,
                'keterangan'      => filled($item['keterangan'] ?? null)
                    ? trim(str_replace('.', '', $item['keterangan']))
                    : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        ProduksiProdusen::insert($rows);

        $jumlah = count($rows);
        return redirect()->back()->with('success',
            "Terima kasih, {$namaResponden}! {$jumlah} komoditas berhasil dicatat."
        );
    }

    /**
     * Hapus satu entri produksi (dari admin, bukan publik — tapi sediakan jika diperlukan).
     */
    public function destroy(string $token, int $id): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        $item = ProduksiProdusen::where('id', $id)->where('id_sesi', $sesi->id)->firstOrFail();
        $item->delete();

        return redirect()->back()->with('success', 'Data produksi berhasil dihapus.');
    }
}
