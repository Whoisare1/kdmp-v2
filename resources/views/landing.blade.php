<x-layouts.guest title="Ngobar — Pemetaan Potensi Desa & Barter Komoditi Lokal">
<style>
    .hero-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.18; pointer-events: none; }
    .gradient-text { background: linear-gradient(135deg, #f5f4ee 0%, #b98a2c 40%, #4f7351 80%, #f5f4ee 100%); background-size: 300% 300%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: gradientShift 6s ease infinite; }
    @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
    @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
    .float-anim { animation: float 5s ease-in-out infinite; }
    .nav-blur { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
    .feature-card { transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease; }
    .feature-card:hover { transform: translateY(-6px); box-shadow: 0 20px 60px rgba(0,0,0,0.4); border-color: rgba(185,138,44,0.33); }
    .cta-glow { box-shadow: 0 0 40px rgba(185,138,44,0.3), 0 4px 24px rgba(0,0,0,0.4); transition: box-shadow 0.3s ease, transform 0.2s ease; }
    .cta-glow:hover { box-shadow: 0 0 60px rgba(185,138,44,0.5), 0 8px 32px rgba(0,0,0,0.5); transform: scale(1.02); }
    .commodity-tag { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px; background: rgba(79, 115, 81, 0.12); border: 1px solid rgba(79, 115, 81, 0.25); font-size: 13px; color: #e3ead9; transition: all 0.2s ease; }
    .commodity-tag:hover { background: rgba(79, 115, 81, 0.22); border-color: rgba(79, 115, 81, 0.45); }
    .map-pattern { background-image: linear-gradient(rgba(185,138,44,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(185,138,44,0.06) 1px, transparent 1px); background-size: 40px 40px; }
    .badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-family: "IBM Plex Mono", monospace; letter-spacing: 0.08em; text-transform: uppercase; }
    .reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.6s ease, transform 0.6s ease; }
    .reveal.visible { opacity: 1; transform: translateY(0); }
    ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #1c211a; } ::-webkit-scrollbar-thumb { background: #333c2d; border-radius: 3px; }
</style>

{{-- NAVBAR --}}
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-ink-800/60 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-padi-500 flex items-center justify-center text-ink-950 font-display font-bold text-sm">N</div>
            <span class="font-display font-semibold text-paper-50 text-lg">Ngobar</span>
            <span class="badge bg-sawah-500/15 text-sawah-100 border border-sawah-500/25 ml-1">Beta</span>
        </div>
        <div class="hidden md:flex items-center gap-8">
            <a href="#overview" class="text-sm text-paper-300 hover:text-paper-50 transition-colors">Fitur</a>
            <a href="#modul" class="text-sm text-paper-300 hover:text-paper-50 transition-colors">Modul</a>
            <a href="#cara-kerja" class="text-sm text-paper-300 hover:text-paper-50 transition-colors">Cara Kerja</a>
            <a href="#komoditi" class="text-sm text-paper-300 hover:text-paper-50 transition-colors">Komoditi</a>
            <a href="#tentang" class="text-sm text-paper-300 hover:text-paper-50 transition-colors">Tentang</a>
        </div>
        <a href="{{ route("login") }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-medium text-sm transition-all duration-200 hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Masuk
        </a>
    </div>
</nav>

{{-- HERO --}}
<section class="relative min-h-screen flex items-center pt-16 map-pattern overflow-hidden">
    <div class="hero-blob" style="width:600px;height:600px;background:#b98a2c;top:-160px;right:-80px;"></div>
    <div class="hero-blob" style="width:500px;height:500px;background:#4f7351;bottom:0;left:-80px;"></div>
    <div class="max-w-7xl mx-auto px-6 py-24 grid lg:grid-cols-2 gap-16 items-center">
        <div class="space-y-8">
            <div class="space-y-4">
                <span class="badge bg-padi-500/15 text-padi-100 border border-padi-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-padi-500 animate-pulse inline-block"></span>
                    Sistem Desa Digital
                </span>
                <h1 class="font-display text-5xl xl:text-6xl font-semibold leading-tight text-paper-50">
                    Pemetaan Potensi Desa &amp; <span class="gradient-text">Barter Komoditi</span> Lokal
                </h1>
                <p class="text-lg text-paper-300/80 leading-relaxed max-w-xl">
                    <strong class="text-padi-100 font-medium">Ngobar</strong> membantu desa memetakan hasil bumi, mengelola stok komoditi, dan memfasilitasi barter antar-desa secara transparan dan terdigitalisasi.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(["🌾 Padi","🥜 Kacang","🐟 Ikan","🌽 Jagung","🥥 Kelapa","🍌 Pisang","🥬 Sayuran","🏺 Olahan"] as $tag)
                    <span class="commodity-tag">{{ $tag }}</span>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route("login") }}" class="cta-glow inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-padi-500 text-ink-950 font-semibold text-sm">
                    Mulai Sekarang
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="#fitur" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-ink-600 text-paper-200 hover:border-padi-500/50 hover:text-padi-100 font-medium text-sm transition-all duration-200">
                    Lihat Fitur
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
            </div>
            <div class="pt-4 grid grid-cols-3 gap-6 border-t border-ink-700">
                @foreach([["9+","Modul Aktif"],["50+","Jenis Komoditi"],["Real-time","Pembaruan Stok"]] as [$val,$label])
                    <div>
                        <p class="font-display text-2xl font-semibold text-padi-100">{{ $val }}</p>
                        <p class="text-xs text-paper-400 mt-1">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="relative flex items-center justify-center">
            <div class="absolute inset-0 rounded-3xl blur-3xl" style="background:linear-gradient(135deg,rgba(185,138,44,0.1),rgba(79,115,81,0.1))"></div>
            <div class="relative float-anim rounded-2xl overflow-hidden border border-ink-600/50 shadow-2xl max-w-lg w-full">
                <img src="/ngobar_hero.jpg" alt="Ilustrasi desa dan pasar komoditi lokal" class="w-full object-cover">
                <div class="absolute bottom-4 left-4 right-4 rounded-xl p-3 border border-ink-700/50" style="background:rgba(28,33,26,0.85);backdrop-filter:blur(12px)">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" style="background:rgba(79,115,81,0.2);border:1px solid rgba(79,115,81,0.3)">📍</div>
                        <div>
                            <p class="text-xs font-medium text-paper-50">Desa Merah Putih</p>
                            <p class="text-xs text-paper-400">3 komoditi tersedia untuk barter</p>
                        </div>
                        <span class="badge ml-auto" style="background:rgba(79,115,81,0.2);color:#e3ead9;border:1px solid rgba(79,115,81,0.3)">Aktif</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-paper-500">
        <span class="text-xs font-mono">scroll</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
</section>

{{-- OVERVIEW (Keunggulan Utama) --}}
<section id="overview" class="py-24 relative">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="badge bg-padi-500/15 text-padi-100 border border-padi-500/30 mb-4">Keunggulan Platform</span>
            <h2 class="font-display text-4xl font-semibold text-paper-50 mt-2">Satu Sistem, Semua Kebutuhan Desa</h2>
            <p class="text-paper-300/70 mt-3 max-w-xl mx-auto">Dari pencatatan data warga, pengelolaan gudang, pembelian, penjualan, hingga laporan akuntansi — terintegrasi dalam satu platform.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            @foreach([
                ["icon"=>"🔗","title"=>"Terintegrasi Penuh","desc"=>"Setiap modul terhubung satu sama lain. Data survei mengalir ke gudang, gudang ke pembelian, pembelian ke akuntansi — tanpa input ulang."],
                ["icon"=>"🏘️","title"=>"Multi-Desa (Multi-Tenant)","desc"=>"Setiap desa punya data yang terpisah dan aman. Satu instalasi bisa melayani ratusan desa sekaligus tanpa data tercampur."],
                ["icon"=>"📱","title"=>"Ramah Perangkat Desa","desc"=>"Tampilan responsif dan ringan. Bisa diakses dari HP atau laptop dengan koneksi internet seadanya di wilayah pedesaan."],
            ] as $i => $card)
                <div class="feature-card rounded-2xl border border-ink-700 bg-ink-900 p-6 reveal" style="transition-delay:{{ $i * 100 }}ms">
                    <div class="w-12 h-12 rounded-xl bg-padi-500/15 border border-padi-500/25 flex items-center justify-center text-2xl mb-4">{{ $card["icon"] }}</div>
                    <h3 class="font-display text-lg font-semibold text-paper-50 mb-2">{{ $card["title"] }}</h3>
                    <p class="text-sm text-paper-300/75 leading-relaxed">{{ $card["desc"] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- MODUL LENGKAP --}}
<section id="modul" class="py-24 map-pattern" style="background-color:rgba(20,23,15,0.6)">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="badge bg-sawah-500/15 text-sawah-100 border border-sawah-500/30 mb-4">9 Modul Sistem</span>
            <h2 class="font-display text-4xl font-semibold text-paper-50 mt-2">Modul Lengkap dari Hulu ke Hilir</h2>
            <p class="text-paper-300/70 mt-3 max-w-2xl mx-auto">
                Ngobar dirancang dengan arsitektur modular — setiap modul bisa berdiri sendiri maupun bekerja bersama modul lainnya.
                Mulai dari survei kebutuhan warga sampai tutup buku tahunan, semua tersedia.
            </p>
        </div>

        {{-- Module Grid --}}
        @php
        $modules = [
            [
                "no" => "M0",
                "icon" => "🗄️",
                "color" => "padi",
                "title" => "Master Data & Konfigurasi",
                "desc" => "Fondasi seluruh sistem. Kelola data induk seperti profil desa, jenis komoditi, satuan ukur, kode akun, dan pengguna sistem. Perubahan di sini otomatis berlaku ke seluruh modul.",
                "tags" => ["Profil Desa", "Master Komoditi", "Kode Akun", "Manajemen User"],
                "badge" => "Fondasi",
            ],
            [
                "no" => "M1",
                "icon" => "📋",
                "color" => "sawah",
                "title" => "Survei Kebutuhan Warga",
                "desc" => "Kumpulkan data kebutuhan dan potensi langsung dari warga secara digital. Form survei bisa diisi dari HP, data langsung masuk ke sistem dan bisa divalidasi perangkat desa.",
                "tags" => ["Form Digital", "Validasi Perangkat", "Rekap Otomatis", "Riwayat Survei"],
                "badge" => "Input Data",
            ],
            [
                "no" => "M2",
                "icon" => "📊",
                "color" => "padi",
                "title" => "Kalkulasi Kebutuhan",
                "desc" => "Olah hasil survei menjadi estimasi kebutuhan komoditi desa secara keseluruhan. Sistem menghitung gap antara ketersediaan dan kebutuhan, serta memberikan rekomendasi pengadaan.",
                "tags" => ["Gap Analysis", "Estimasi Kebutuhan", "Rekomendasi Pengadaan"],
                "badge" => "Analitik",
            ],
            [
                "no" => "M3",
                "icon" => "📝",
                "color" => "sawah",
                "title" => "Rencana Pengadaan",
                "desc" => "Buat rencana pengadaan berdasarkan hasil kalkulasi. Tentukan prioritas, sumber pengadaan (beli, barter, atau donasi), dan jadwal pemenuhan kebutuhan komoditi desa.",
                "tags" => ["Rencana Beli", "Rencana Barter", "Jadwal Pengadaan", "Persetujuan"],
                "badge" => "Perencanaan",
            ],
            [
                "no" => "M4",
                "icon" => "🏭",
                "color" => "padi",
                "title" => "Gudang & Manajemen Stok",
                "desc" => "Kelola seluruh pergerakan barang secara real-time. Catat penerimaan barang (GRN), pengeluaran, opname fisik, dan kerusakan stok. Kartu stok otomatis ter-update setiap transaksi.",
                "tags" => ["Penerimaan Barang", "Kartu Stok", "Opname Fisik", "Laporan Kerusakan", "Stok Real-time"],
                "badge" => "Operasional",
            ],
            [
                "no" => "M5",
                "icon" => "🛒",
                "color" => "merah",
                "title" => "Pembelian (Purchase Order)",
                "desc" => "Kelola proses pengadaan barang dari pemasok. Buat PO, terima GRN (Goods Receipt Note), catat hutang dagang, dan pantau status pembayaran. Terintegrasi langsung dengan gudang dan akuntansi.",
                "tags" => ["Purchase Order", "GRN", "Hutang Dagang", "Approval PO", "Riwayat Supplier"],
                "badge" => "Transaksi",
            ],
            [
                "no" => "M6",
                "icon" => "💰",
                "color" => "sawah",
                "title" => "Penjualan & Distribusi",
                "desc" => "Catat transaksi penjualan komoditi ke warga maupun antar-desa. Kelola piutang, proses pelunasan, dan pantau performa penjualan per komoditi atau per periode.",
                "tags" => ["Sales Order", "Faktur Penjualan", "Piutang", "Pelunasan", "Laporan Penjualan"],
                "badge" => "Transaksi",
            ],
            [
                "no" => "M7",
                "icon" => "🔄",
                "color" => "padi",
                "title" => "Konsinyasi & Barter Antar-Desa",
                "desc" => "Modul inti Ngobar. Titipkan komoditi ke desa lain (konsinyasi) atau lakukan barter langsung. Sistem mencatat pengiriman, rekonsiliasi, dan status setiap transaksi antar-desa secara transparan.",
                "tags" => ["Titip Jual", "Barter Komoditi", "Pengiriman", "Rekonsiliasi", "Konfirmasi 2 Pihak"],
                "badge" => "Unggulan",
            ],
            [
                "no" => "M8",
                "icon" => "📒",
                "color" => "sawah",
                "title" => "Akuntansi & Laporan Keuangan",
                "desc" => "Pembukuan otomatis dari setiap transaksi. Lihat Buku Besar, Neraca Saldo, Laporan Laba Rugi, dan Neraca dalam hitungan detik. Fitur tutup bulan dan tutup tahun menjaga integritas data keuangan.",
                "tags" => ["Buku Besar", "Neraca Saldo", "Laba Rugi", "Neraca", "Tutup Bulan", "Tutup Tahun"],
                "badge" => "Keuangan",
            ],
        ];
        @endphp

        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($modules as $i => $m)
                <div class="feature-card group relative rounded-2xl border border-ink-700 bg-ink-900/80 p-5 reveal" style="transition-delay:{{ $i * 70 }}ms">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl shrink-0
                                @if($m["color"]==="padi") bg-padi-500/15 border border-padi-500/25
                                @elseif($m["color"]==="sawah") bg-sawah-500/15 border border-sawah-500/25
                                @else bg-merah-500/15 border border-merah-500/25 @endif">
                                {{ $m["icon"] }}
                            </div>
                            <div>
                                <span class="font-mono text-xs
                                    @if($m["color"]==="padi") text-padi-400
                                    @elseif($m["color"]==="sawah") text-sawah-500
                                    @else text-merah-400 @endif">{{ $m["no"] }}</span>
                                <h3 class="font-display text-base font-semibold text-paper-50 leading-tight">{{ $m["title"] }}</h3>
                            </div>
                        </div>
                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full font-mono
                            @if($m["badge"]==="Unggulan") bg-padi-500/20 text-padi-100 border border-padi-500/30
                            @elseif($m["badge"]==="Keuangan") bg-sawah-500/20 text-sawah-100 border border-sawah-500/30
                            @elseif($m["badge"]==="Transaksi") bg-merah-500/15 text-merah-100 border border-merah-500/25
                            @else bg-ink-700 text-paper-400 border border-ink-600 @endif">
                            {{ $m["badge"] }}
                        </span>
                    </div>

                    {{-- Desc --}}
                    <p class="text-sm text-paper-300/75 leading-relaxed mb-4">{{ $m["desc"] }}</p>

                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($m["tags"] as $tag)
                            <span class="text-xs px-2 py-0.5 rounded-md
                                @if($m["color"]==="padi") bg-padi-500/8 text-padi-100/70
                                @elseif($m["color"]==="sawah") bg-sawah-500/8 text-sawah-100/70
                                @else bg-merah-500/8 text-merah-100/70 @endif
                                border border-ink-700">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Module flow diagram --}}
        <div class="mt-16 reveal">
            <p class="text-center text-xs font-mono text-paper-500 mb-6 uppercase tracking-widest">Alur Antar Modul</p>
            <div class="flex flex-wrap justify-center items-center gap-2 text-xs">
                @foreach([
                    ["M0","Master Data","padi"],
                    ["→","",""],
                    ["M1","Survei","sawah"],
                    ["→","",""],
                    ["M2","Kalkulasi","padi"],
                    ["→","",""],
                    ["M3","Rencana","sawah"],
                    ["→","",""],
                    ["M4","Gudang","padi"],
                    ["→","",""],
                    ["M5","Pembelian","merah"],
                    ["→","",""],
                    ["M6","Penjualan","sawah"],
                    ["→","",""],
                    ["M7","Konsinyasi","padi"],
                    ["→","",""],
                    ["M8","Akuntansi","sawah"],
                ] as [$no,$label,$color])
                    @if($no === "→")
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    @else
                        <div class="flex flex-col items-center gap-1 px-3 py-2 rounded-lg border
                            @if($color==="padi") bg-padi-500/10 border-padi-500/25
                            @elseif($color==="sawah") bg-sawah-500/10 border-sawah-500/25
                            @else bg-merah-500/10 border-merah-500/25 @endif">
                            <span class="font-mono font-bold
                                @if($color==="padi") text-padi-400
                                @elseif($color==="sawah") text-sawah-500
                                @else text-merah-400 @endif">{{ $no }}</span>
                            <span class="text-paper-400 text-center" style="font-size:10px">{{ $label }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- CARA KERJA --}}
<section id="cara-kerja" class="py-24 map-pattern" style="background-color:rgba(28,33,26,0.5)">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="badge bg-sawah-500/15 text-sawah-100 border border-sawah-500/30 mb-4">Cara Kerja</span>
            <h2 class="font-display text-4xl font-semibold text-paper-50 mt-2">Mudah Digunakan, Tepat Sasaran</h2>
            <p class="text-paper-300/70 mt-3 max-w-lg mx-auto">Hanya 4 langkah untuk memulai barter komoditi antar-desa yang terdigitalisasi.</p>
        </div>
        <div class="grid md:grid-cols-4 gap-6">
            @php
            $steps = [
                ["num"=>"01","icon"=>"🏘️","title"=>"Daftar Desa","desc"=>"Daftarkan desa Anda dan lengkapi profil dengan data wilayah dan potensi awal."],
                ["num"=>"02","icon"=>"🌾","title"=>"Input Komoditi","desc"=>"Catat stok komoditi yang tersedia — hasil panen, ternak, atau olahan desa."],
                ["num"=>"03","icon"=>"🔍","title"=>"Cari Mitra Desa","desc"=>"Temukan desa lain yang punya komoditi berbeda dan ajukan penawaran barter."],
                ["num"=>"04","icon"=>"✅","title"=>"Barter Terlaksana","desc"=>"Konfirmasi transaksi, sistem mencatat riwayat, dan laporan tersimpan otomatis."],
            ];
            @endphp
            @foreach($steps as $i => $step)
                <div class="relative flex flex-col items-center text-center reveal" style="transition-delay:{{ $i * 100 }}ms">
                    @if($i < 3)
                        <div class="hidden md:block absolute top-7 h-px" style="left:60%;right:0;background:linear-gradient(to right,rgba(185,138,44,0.4),transparent)"></div>
                    @endif
                    <div class="relative w-14 h-14 rounded-2xl bg-padi-500/15 border border-padi-500/30 flex items-center justify-center text-2xl mb-4 z-10">
                        {{ $step["icon"] }}
                        <div class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-padi-500 flex items-center justify-center">
                            <span class="font-mono text-ink-950" style="font-size:9px;font-weight:700">{{ $step["num"] }}</span>
                        </div>
                    </div>
                    <h3 class="font-display text-base font-semibold text-paper-50 mb-2">{{ $step["title"] }}</h3>
                    <p class="text-xs text-paper-400 leading-relaxed">{{ $step["desc"] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- KOMODITI --}}
<section id="komoditi" class="py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="relative reveal">
                <div class="absolute inset-0 rounded-3xl blur-3xl" style="background:linear-gradient(135deg,rgba(79,115,81,0.15),rgba(185,138,44,0.1))"></div>
                <div class="relative rounded-2xl overflow-hidden border border-ink-600/50 shadow-2xl bg-ink-900 p-6">
                    <img src="/ngobar_commodities.jpg" alt="Komoditi lokal desa" class="w-full object-contain mx-auto" style="max-height:20rem">
                    <p class="text-center text-xs text-paper-400 mt-3 font-mono">Komoditi Tersedia di Platform Ngobar</p>
                </div>
            </div>
            <div class="space-y-6 reveal">
                <span class="badge bg-sawah-500/15 text-sawah-100 border border-sawah-500/30">Komoditi Lokal</span>
                <h2 class="font-display text-4xl font-semibold text-paper-50">Semua Hasil Bumi Desa, Terhubung Digital</h2>
                <p class="text-paper-300/75 leading-relaxed">Ngobar mendukung berbagai jenis komoditi dari seluruh sektor ekonomi desa. Tidak ada komoditi yang terlalu kecil — semuanya bisa bernilai dalam sistem barter yang tepat.</p>
                <div class="space-y-3">
                    @foreach([
                        ["🌾","Pertanian","Padi, Jagung, Kedelai, Singkong, Ubi"],
                        ["🐄","Peternakan","Sapi, Kambing, Ayam, Telur, Susu"],
                        ["🐟","Perikanan","Ikan Tawar, Udang, Lele, Bandeng"],
                        ["🥥","Perkebunan","Kelapa, Kopi, Kakao, Pisang, Durian"],
                        ["🏺","Olahan & UMKM","Keripik, Anyaman, Batik, Gula Aren"],
                    ] as [$emoji,$name,$items])
                        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-ink-800 border border-ink-700 hover:border-sawah-500/30 transition-all duration-200 group cursor-default">
                            <span class="text-xl mt-0.5">{{ $emoji }}</span>
                            <div>
                                <p class="text-sm font-medium text-paper-100 group-hover:text-sawah-100 transition-colors">{{ $name }}</p>
                                <p class="text-xs text-paper-400 mt-0.5">{{ $items }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-paper-600 group-hover:text-sawah-500 ml-auto mt-1 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="py-16 border-y border-ink-700" style="background:linear-gradient(to right,rgba(61,92,63,0.2),rgba(38,45,34,0.5),rgba(147,112,31,0.2))">
    <div class="max-w-5xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach([["🏘️","Antar-Desa","Jaringan Barter"],["📦","50+","Jenis Komoditi"],["⚡","Real-time","Pembaruan Stok"],["🔒","Aman","Data Terenkripsi"]] as [$icon,$val,$label])
                <div class="reveal">
                    <div class="text-3xl mb-2">{{ $icon }}</div>
                    <p class="font-display text-2xl font-semibold text-padi-100">{{ $val }}</p>
                    <p class="text-xs text-paper-400 mt-1">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- TENTANG --}}
<section id="tentang" class="py-24">
    <div class="max-w-5xl mx-auto px-6">
        <div class="rounded-3xl border border-ink-700 bg-ink-900 p-8 md:p-12 reveal relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full -translate-y-1/2 translate-x-1/2" style="background:rgba(185,138,44,0.05)"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full translate-y-1/2 -translate-x-1/2" style="background:rgba(79,115,81,0.05)"></div>
            <div class="relative grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <span class="badge bg-merah-500/15 text-merah-100 border border-merah-500/25 mb-4">Tentang Ngobar</span>
                    <h2 class="font-display text-3xl font-semibold text-paper-50 mt-3 mb-4">Dari Skripsi untuk Desa Indonesia</h2>
                    <p class="text-paper-300/75 leading-relaxed text-sm mb-4"><strong class="text-paper-100">Ngobar</strong> (<em>Ngobrol Barter</em>) adalah sistem informasi berbasis web yang dikembangkan sebagai capstone project Teknik Informatika. Tujuannya: membantu desa-desa di Indonesia mengelola potensi lokal dan bertransaksi satu sama lain tanpa bergantung pada pasar formal yang jauh.</p>
                    <p class="text-paper-300/75 leading-relaxed text-sm">Dibangun dengan <strong class="text-paper-100">Laravel + Vite + Tailwind CSS</strong>, sistem ini dirancang agar mudah dioperasikan oleh perangkat desa dengan koneksi internet terbatas sekalipun.</p>
                </div>
                <div class="space-y-4">
                    @foreach([["Framework","Laravel 11"],["Frontend","Tailwind CSS v4 + Vite"],["Database","MySQL (Multi-tenant)"],["Kategori","Capstone — Teknik Informatika"],["Status","Beta — Aktif Dikembangkan"]] as [$label,$val])
                        <div class="flex justify-between items-center py-2.5 border-b border-ink-700 last:border-0">
                            <span class="text-xs font-mono text-paper-400">{{ $label }}</span>
                            <span class="text-xs font-medium text-paper-100 bg-ink-800 px-2.5 py-1 rounded-md">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-24 relative overflow-hidden map-pattern">
    <div class="hero-blob" style="width:400px;height:400px;background:#b98a2c;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.08"></div>
    <div class="relative max-w-3xl mx-auto px-6 text-center reveal">
        <span class="text-4xl mb-6 block">🤝</span>
        <h2 class="font-display text-4xl md:text-5xl font-semibold text-paper-50 mb-4">Siap Wujudkan Desa<br>yang Mandiri?</h2>
        <p class="text-paper-300/70 mb-10 text-lg leading-relaxed">Bergabunglah dengan Ngobar dan mulai pemetaan potensi desa serta barter komoditi lokal hari ini.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route("login") }}" class="cta-glow inline-flex items-center gap-3 px-8 py-4 rounded-xl bg-padi-500 text-ink-950 font-semibold text-base">🚀 Masuk ke Sistem</a>
            <a href="#fitur" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl border border-ink-600 text-paper-200 hover:border-padi-500/50 hover:text-padi-100 font-medium text-base transition-all duration-200">Pelajari Lebih Lanjut</a>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="border-t border-ink-800 py-10">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-padi-500 flex items-center justify-center text-ink-950 font-display font-bold text-xs">N</div>
            <span class="font-display font-semibold text-paper-50">Ngobar</span>
            <span class="text-paper-500 text-sm ml-2">— Pemetaan Potensi &amp; Barter Komoditi Desa</span>
        </div>
        <div class="flex items-center gap-6">
            <a href="#fitur" class="text-xs text-paper-500 hover:text-paper-300 transition-colors">Fitur</a>
            <a href="#cara-kerja" class="text-xs text-paper-500 hover:text-paper-300 transition-colors">Cara Kerja</a>
            <a href="#tentang" class="text-xs text-paper-500 hover:text-paper-300 transition-colors">Tentang</a>
            <a href="{{ route("login") }}" class="text-xs text-padi-400 hover:text-padi-300 transition-colors font-medium">Masuk →</a>
        </div>
        <p class="text-xs text-paper-600 font-mono">© 2025 Ngobar. Capstone Project.</p>
    </div>
</footer>

<script>
    const navbar = document.getElementById("navbar");
    window.addEventListener("scroll", () => {
        navbar.style.background = window.scrollY > 20 ? "rgba(20,23,15,0.92)" : "transparent";
    });
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add("visible"); });
    }, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });
    document.querySelectorAll(".reveal").forEach(el => observer.observe(el));
    document.querySelectorAll("a[href^=\"#\"]").forEach(a => {
        a.addEventListener("click", e => {
            e.preventDefault();
            const t = document.querySelector(a.getAttribute("href"));
            if (t) t.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    });
</script>
</x-layouts.guest>
