<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Produsen — {{ $sesi->wilayah->nama ?? 'Desa' }}</title>
    <meta name="description" content="Form survei potensi produksi desa untuk Kelompok Tani dan Pelaku Ekraf">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #ecfdf5 100%);
            min-height: 100vh;
            margin: 0;
            color: #1c211a;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ── */
        .page-header {
            background: linear-gradient(135deg, #15803d 0%, #059669 100%);
            color: #fff;
            padding: 2rem 1rem 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .page-header::after {
            content: '';
            position: absolute;
            left: -40px; bottom: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            border-radius: 16px;
            background: rgba(255,255,255,0.2);
            margin-bottom: 1rem;
        }
        .header-title {
            font-size: clamp(1.25rem, 5vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 0.5rem;
        }
        .header-sub {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.85);
            margin: 0;
        }
        .header-badge {
            display: inline-block;
            margin-top: 0.75rem;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.2rem 0.75rem;
            border-radius: 999px;
        }

        /* ── Cards ── */
        .main-content {
            max-width: 640px;
            margin: -1.5rem auto 0;
            padding: 0 1rem 3rem;
            position: relative;
            z-index: 10;
        }
        .card {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }
        .card-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .step-badge {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-header-text { flex: 1; }
        .card-header-title { font-size: 0.95rem; font-weight: 700; color: #fff; margin: 0; }
        .card-header-sub { font-size: 0.72rem; color: rgba(255,255,255,0.8); margin: 0.1rem 0 0; }
        .card-body { padding: 1.25rem; }
        .card-body + .card-body { border-top: 1px solid #ebe8de; }

        /* ── Form Fields ── */
        .field { margin-bottom: 1rem; }
        .field:last-child { margin-bottom: 0; }
        .field-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #454f3d;
            margin-bottom: 0.4rem;
        }
        .required { color: #c1483a; }

        /* Input wrapper: input + mic button */
        .input-wrap {
            position: relative;
            display: flex;
            align-items: stretch;
        }
        .input-wrap input,
        .input-wrap textarea,
        .input-wrap select {
            width: 100%;
            padding: 0.65rem 3rem 0.65rem 0.875rem;
            border: 1.5px solid #ddd9cb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
            color: #1c211a;
            transition: border-color 0.15s;
            appearance: none;
            -webkit-appearance: none;
        }
        .input-wrap select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23454f3d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 2.5rem center;
            background-size: 1rem;
            padding-right: 4rem;
        }
        /* select without mic (no x-data speechInput) */
        .input-wrap.no-mic select,
        .input-wrap.no-mic input {
            padding-right: 0.875rem;
            background-position: right 0.75rem center;
        }
        .input-wrap input:focus,
        .input-wrap textarea:focus,
        .input-wrap select:focus {
            outline: none;
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(21,128,61,0.12);
        }
        .input-wrap textarea {
            resize: none;
            min-height: 72px;
        }
        /* Number inputs: bold */
        .input-wrap input[type="number"] {
            font-weight: 700;
            font-size: 1rem;
        }

        /* Mic button — absolute, perfectly centered vertically */
        .mic-btn {
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            transition: background 0.15s, color 0.15s;
            padding: 0;
            flex-shrink: 0;
        }
        .mic-btn:hover { background: #f0fdf4; color: #15803d; }
        .mic-btn.listening {
            background: #fef2f2;
            color: #dc2626;
            animation: pulse-red 1s infinite;
        }
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        /* For textarea: mic is top-aligned */
        .input-wrap.textarea-wrap .mic-btn {
            top: 0.4rem;
            transform: none;
        }

        /* Two-column grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        @media (max-width: 400px) { .grid-2 { grid-template-columns: 1fr; } }

        /* ── Suggestion chips ── */
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }
        .chip {
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            border: 1.5px solid #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }
        .chip:hover { background: #dcfce7; border-color: #4ade80; }
        .chips-label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #454f3d;
            margin-bottom: 0.4rem;
        }

        /* ── Add-to-cart button ── */
        .btn-add {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 2px dashed #4ade80;
            background: #f0fdf4;
            color: #15803d;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.15s;
            font-family: inherit;
        }
        .btn-add:hover { background: #dcfce7; border-color: #22c55e; }

        /* ── Cart items ── */
        .cart-item {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-bottom: 1px solid #f5f4ee;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1c211a;
            margin: 0 0 0.2rem;
        }
        .cart-item-meta {
            font-size: 0.78rem;
            color: #454f3d;
            margin: 0;
        }
        .cart-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            margin-left: 0.4rem;
        }
        .cart-badge-tani { background: #dcfce7; color: #15803d; }
        .cart-badge-ekraf { background: #f3e8ff; color: #7c3aed; }
        .btn-del {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            background: #fff1f2;
            color: #dc2626;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s;
        }
        .btn-del:hover { background: #fee2e2; }

        /* ── Edit Modal ── */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(0,0,0,0.5);
            display: flex; align-items: flex-end; justify-content: center;
            padding: 0;
        }
        @media (min-width: 480px) {
            .modal-overlay { align-items: center; padding: 1rem; }
        }
        .modal-box {
            background: #fff;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 -8px 40px rgba(0,0,0,0.15);
        }
        @media (min-width: 480px) {
            .modal-box { border-radius: 16px; }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid #ebe8de;
            position: sticky; top: 0; background: #fff; z-index: 1;
        }
        .modal-title { font-size: 1rem; font-weight: 700; color: #1c211a; margin: 0; }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            background: #f5f4ee; color: #454f3d; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-body { padding: 1.25rem; }
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #ebe8de;
            display: flex; gap: 0.75rem;
        }
        .btn-cancel {
            flex: 1; padding: 0.75rem; border-radius: 10px;
            border: 1.5px solid #ddd9cb; background: #fff;
            color: #454f3d; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; font-family: inherit;
        }
        .btn-save {
            flex: 2; padding: 0.75rem; border-radius: 10px;
            border: none; background: linear-gradient(135deg,#15803d,#059669);
            color: #fff; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
            box-shadow: 0 3px 10px rgba(21,128,61,0.3);
        }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #15803d 0%, #059669 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 16px rgba(21,128,61,0.35);
            transition: all 0.15s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #166534 0%, #047857 100%);
            box-shadow: 0 6px 20px rgba(21,128,61,0.45);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); box-shadow: none; }

        /* ── Alerts ── */
        .alert {
            border-radius: 12px;
            padding: 0.875rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
            font-size: 0.85rem;
        }
        .alert-success { background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; }
        .alert-error { background: #fff1f2; border: 1.5px solid #fca5a5; color: #b91c1c; }
        .alert svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: 1px; }
        .alert-error-list { list-style: disc; padding-left: 1.2rem; margin: 0.25rem 0 0; }

        /* ── Error msg ── */
        .err-msg {
            font-size: 0.8rem;
            color: #dc2626;
            font-weight: 600;
            text-align: center;
            margin-top: 0.5rem;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 0.72rem;
            color: #888;
            padding: 0 1rem 2rem;
        }

        /* ── Cart count badge in card header ── */
        .count-badge {
            background: rgba(255,255,255,0.25);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.1rem 0.6rem;
            border-radius: 999px;
            margin-left: auto;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="produsenSurvei()">

    {{-- ══ HEADER ══ --}}
    <div class="page-header">
        <div style="position:relative;z-index:1;">
            <div class="header-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="header-title">Survei Potensi Produksi Desa</h1>
            <p class="header-sub">
                Desa <strong>{{ $sesi->wilayah->nama ?? '-' }}</strong>
                &bull; {{ \Carbon\Carbon::createFromFormat('m', $sesi->bulan)->translatedFormat('F') }} {{ $sesi->tahun }}
            </p>

        </div>
    </div>

    {{-- ══ CONTENT ══ --}}
    <div class="main-content">

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <div style="font-weight:600;">Ada kesalahan:</div>
                    <ul class="alert-error-list">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ══ STEP 1 — Identitas ══ --}}
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#15803d,#059669);">
                <div class="step-badge">1</div>
                <div class="card-header-text">
                    <p class="card-header-title">Identitas Responden</p>
                </div>
            </div>
            <div class="card-body">
                {{-- Nama --}}
                <div class="field" x-data="micInput(() => namaResponden, v => namaResponden = v)">
                    <label class="field-label">Nama Lengkap Anda <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input type="text" x-ref="el"
                               :value="namaResponden"
                               @input="namaResponden = $event.target.value"
                               placeholder="Ketik atau ucapkan nama Anda...">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Kategori + Sub --}}
                <div class="grid-2">
                    <div class="field">
                        <label class="field-label">Kategori <span class="required">*</span></label>
                        <div class="input-wrap no-mic">
                            <select x-model="kategori" @change="subKategori=''">
                                <option value="">-- Pilih --</option>
                                <option value="Kelompok Tani">🌾 Kelompok Tani</option>
                                <option value="Ekraf">🎨 Ekraf</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" x-show="kategori" x-cloak>
                        <label class="field-label">Sub Kategori</label>
                        <div class="input-wrap no-mic">
                            <select x-model="subKategori">
                                <option value="">-- Pilih --</option>
                                <template x-if="kategori === 'Kelompok Tani'">
                                    <optgroup label="Kelompok Tani">
                                        <option value="Pertanian">🌾 Pertanian</option>
                                        <option value="Perkebunan">🌴 Perkebunan</option>
                                        <option value="Peternakan">🐄 Peternakan</option>
                                        <option value="Kelautan">🌊 Kelautan</option>
                                    </optgroup>
                                </template>
                                <template x-if="kategori === 'Ekraf'">
                                    <optgroup label="Ekonomi Kreatif">
                                        <option value="Pangan">🍱 Pangan</option>
                                        <option value="Non Pangan">🧶 Non Pangan</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ STEP 2 — Tambah Komoditas ══ --}}
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#0d7f54,#047857);">
                <div class="step-badge">2</div>
                <div class="card-header-text">
                    <p class="card-header-title">Tambah Komoditas</p>
                </div>
            </div>
            <div class="card-body">

                {{-- Saran chips --}}
                <div x-show="pilihanSaran.length > 0" x-cloak>
                    <div class="chips-label">Komoditas umum (ketuk untuk pilih):</div>
                    <div class="chips">
                        <template x-for="s in pilihanSaran" :key="s">
                            <button type="button" class="chip" @click="namaKmd = s" x-text="s"></button>
                        </template>
                    </div>
                </div>

                {{-- Nama komoditas --}}
                <div class="field" x-data="micInput(() => namaKmd, v => namaKmd = v)">
                    <label class="field-label">Nama Komoditas / Produk <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input type="text" x-ref="el"
                               :value="namaKmd"
                               @input="namaKmd = $event.target.value"
                               placeholder="Beras, Kopi, Anyaman...">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Jumlah + Satuan --}}
                <div class="grid-2">
                    <div class="field" x-data="micInput(() => jumlah, v => jumlah = v, true)">
                        <label class="field-label">Jumlah Produksi</label>
                        <div class="input-wrap">
                            <input type="number" x-ref="el"
                                   :value="jumlah"
                                   @input="jumlah = $event.target.value"
                                   min="0" step="0.01" placeholder="0">
                            <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label">Satuan</label>
                        <div class="input-wrap no-mic">
                            <select x-model="satuan">
                                <option value="">-- Pilih --</option>
                                @foreach($satuanList as $sat)
                                    <option value="{{ $sat }}">{{ $sat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Bulan --}}
                <div class="field">
                    <label class="field-label">Bulan Produksi <span class="required">*</span></label>
                    <div class="input-wrap no-mic">
                        <select x-model="bulan">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach($bulans as $no => $nama)
                                <option value="{{ $no }}" {{ $sesi->bulan == $no ? 'selected' : '' }}>{{ $nama }} {{ $sesi->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p style="font-size:0.72rem;color:#888;margin-top:0.25rem;">Pilih bulan panen atau bulan produksi komoditas ini.</p>
                </div>

                {{-- Keterangan --}}
                <div class="field" x-data="micInput(() => ket, v => ket = v)">
                    <label class="field-label">Keterangan (Opsional)</label>
                    <div class="input-wrap textarea-wrap">
                        <textarea x-ref="el"
                                  :value="ket"
                                  @input="ket = $event.target.value"
                                  placeholder="Catatan tambahan, kondisi panen, dll..."></textarea>
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Tombol tambah ke daftar --}}
                <button type="button" class="btn-add" @click="tambah()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah ke Daftar
                </button>
                <p class="err-msg" x-show="errMsg" x-text="errMsg" x-cloak></p>
            </div>
        </div>

        {{-- ══ STEP 3 — Daftar & Kirim ══ --}}
        <div class="card" x-show="cart.length > 0" x-cloak>
            <div class="card-header" style="background:linear-gradient(135deg,#064e3b,#065f46);">
                <div class="step-badge">3</div>
                <div class="card-header-text">
                    <p class="card-header-title">Daftar Komoditas</p>
                </div>
                <span class="count-badge" x-text="cart.length + ' item'"></span>
            </div>

            {{-- Cart items --}}
            <template x-for="(item, i) in cart" :key="i">
                <div class="cart-item">
                    <div class="cart-item-info">
                        <p class="cart-item-name">
                            <span x-text="item.nama_komoditas"></span>
                            <span class="cart-badge" :class="item.kategori === 'Kelompok Tani' ? 'cart-badge-tani' : 'cart-badge-ekraf'"
                                  x-show="item.sub_kategori" x-text="item.sub_kategori"></span>
                        </p>
                        <p class="cart-item-meta">
                            <span x-show="item.jumlah_produksi">
                                <span x-text="item.jumlah_produksi"></span> <span x-text="item.satuan"></span> &bull;
                            </span>
                            Bulan <span x-text="namaBulan(item.bulan_produksi)"></span>
                            <span x-show="item.keterangan"> &bull; <em x-text="item.keterangan"></em></span>
                        </p>
                    </div>
                    <div style="display:flex;gap:0.4rem;flex-shrink:0;">
                        <button type="button" class="btn-edit" @click="editItem(i)" title="Edit">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button type="button" class="btn-del" @click="cart.splice(i,1)" title="Hapus">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Submit --}}
            <div style="padding:1.25rem;border-top:1px solid #ebe8de;">
                <button type="button" class="btn-submit" @click="submitAll()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Kirim Semua Data
                    <span style="background:rgba(255,255,255,0.2);border-radius:999px;padding:0.1rem 0.5rem;font-size:0.8rem;" x-text="'(' + cart.length + ' Komoditas)'"></span>
                </button>
                <p style="text-align:center;font-size:0.72rem;color:#888;margin-top:0.6rem;">
                    Data akan dicatat ke sistem survei desa.
                </p>
            </div>
        </div>

        <div class="footer">&copy; {{ date('Y') }} Kader Desa Merdeka Pangan</div>
    </div>

    {{-- ══ MODAL EDIT KOMODITAS ══ --}}
    <div class="modal-overlay" x-show="showEditModal" x-cloak @click.self="showEditModal = false"
         style="animation: none;">
        <div class="modal-box">
            <div class="modal-header">
                <p class="modal-title">Edit Komoditas</p>
                <button class="modal-close" @click="showEditModal = false" type="button">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:0.875rem;">
                {{-- Nama Komoditas --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Nama Komoditas <span class="required">*</span></label>
                    <div class="input-wrap no-mic">
                        <input type="text" x-model="editForm.nama_komoditas" placeholder="Nama komoditas..."
                               style="padding-right:0.875rem;">
                    </div>
                </div>
                {{-- Kategori + Sub --}}
                <div class="grid-2">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Kategori <span class="required">*</span></label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.kategori" @change="editForm.sub_kategori=''">
                                <option value="">-- Pilih --</option>
                                <option value="Kelompok Tani">🌾 Kelompok Tani</option>
                                <option value="Ekraf">🎨 Ekraf</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Sub Kategori</label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.sub_kategori">
                                <option value="">-- Pilih --</option>
                                <template x-if="editForm.kategori === 'Kelompok Tani'">
                                    <optgroup label="Kelompok Tani">
                                        <option value="Pertanian">🌾 Pertanian</option>
                                        <option value="Perkebunan">🌴 Perkebunan</option>
                                        <option value="Peternakan">🐄 Peternakan</option>
                                        <option value="Kelautan">🌊 Kelautan</option>
                                    </optgroup>
                                </template>
                                <template x-if="editForm.kategori === 'Ekraf'">
                                    <optgroup label="Ekonomi Kreatif">
                                        <option value="Pangan">🍱 Pangan</option>
                                        <option value="Non Pangan">🧶 Non Pangan</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- Jumlah + Satuan --}}
                <div class="grid-2">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Jumlah Produksi</label>
                        <div class="input-wrap no-mic">
                            <input type="number" x-model="editForm.jumlah_produksi"
                                   min="0" step="0.01" placeholder="0" style="padding-right:0.875rem;font-weight:700;">
                        </div>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Satuan</label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.satuan">
                                <option value="">-- Pilih --</option>
                                <template x-for="s in SATUAN_LIST" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- Bulan --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Bulan Produksi <span class="required">*</span></label>
                    <div class="input-wrap no-mic">
                        <select x-model="editForm.bulan_produksi">
                            <option value="">-- Pilih Bulan --</option>
                            <template x-for="(nama, no) in BULAN_OPTS" :key="no">
                                <option :value="no" x-text="nama + ' {{ $sesi->tahun }}'"></option>
                            </template>
                        </select>
                    </div>
                </div>
                {{-- Keterangan --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Keterangan</label>
                    <div class="input-wrap no-mic textarea-wrap">
                        <textarea x-model="editForm.keterangan"
                                  rows="2" placeholder="Catatan tambahan..."
                                  style="padding-right:0.875rem;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" @click="showEditModal = false">Batal</button>
                <button type="button" class="btn-save" @click="saveEdit()">
                    ✓ Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <script>
        const SARAN = @json($saranKomoditas);
        const BULANS = @json($bulans);
        const ACTION = '{{ route('survei.public.produsen.store', $token) }}';
        const CSRF   = '{{ csrf_token() }}';
        const SATUAN_LIST = @json($satuanList);
        const BULAN_OPTS  = @json($bulans);

        function produsenSurvei() {
            return {
                namaResponden: '', kategori: '', subKategori: '',
                namaKmd: '', jumlah: '', satuan: '', bulan: '{{ $sesi->bulan }}', ket: '',
                cart: [], errMsg: '',

                // Edit modal state
                showEditModal: false,
                editIdx: null,
                editForm: { nama_komoditas:'', kategori:'', sub_kategori:'', jumlah_produksi:'', satuan:'', bulan_produksi:'', keterangan:'' },

                get pilihanSaran() {
                    return this.subKategori && SARAN[this.subKategori] ? SARAN[this.subKategori] : [];
                },
                namaBulan(no) { return BULANS[no] || no; },

                editItem(i) {
                    this.editIdx  = i;
                    this.editForm = { ...this.cart[i] };
                    this.showEditModal = true;
                },
                saveEdit() {
                    if (!this.editForm.nama_komoditas.trim()) { alert('Nama komoditas wajib diisi!'); return; }
                    if (!this.editForm.bulan_produksi) { alert('Pilih bulan produksi!'); return; }
                    this.editForm.nama_komoditas = cap(this.editForm.nama_komoditas.replace(/\./g,''));
                    this.editForm.keterangan = (this.editForm.keterangan || '').replace(/\./g,'');
                    this.cart[this.editIdx] = { ...this.editForm };
                    this.showEditModal = false;
                    this.editIdx = null;
                },

                tambah() {
                    this.errMsg = '';
                    if (!this.namaResponden.trim()) { this.errMsg = 'Nama Anda wajib diisi dulu!'; window.scrollTo({top:0,behavior:'smooth'}); return; }
                    if (!this.kategori) { this.errMsg = 'Pilih Kategori terlebih dahulu!'; return; }
                    if (!this.namaKmd.trim()) { this.errMsg = 'Nama Komoditas wajib diisi!'; return; }
                    if (!this.bulan) { this.errMsg = 'Pilih Bulan Produksi!'; return; }
                    this.cart.push({
                        nama_komoditas: cap(this.namaKmd.replace(/\./g,'')),
                        kategori: this.kategori, sub_kategori: this.subKategori,
                        jumlah_produksi: this.jumlah, satuan: this.satuan,
                        bulan_produksi: this.bulan,
                        keterangan: this.ket.replace(/\./g,''),
                    });
                    this.namaKmd = ''; this.jumlah = ''; this.satuan = ''; this.ket = '';
                },
                submitAll() {
                    if (!this.cart.length) { this.errMsg = 'Tambah minimal 1 komoditas dulu!'; return; }
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = ACTION;
                    addF(form, '_token', CSRF);
                    addF(form, 'nama_responden', this.namaResponden.replace(/\./g,'').trim());
                    this.cart.forEach((it,i) => {
                        addF(form, `komoditas[${i}][nama_komoditas]`,  it.nama_komoditas);
                        addF(form, `komoditas[${i}][kategori]`,        it.kategori);
                        addF(form, `komoditas[${i}][sub_kategori]`,    it.sub_kategori);
                        addF(form, `komoditas[${i}][jumlah_produksi]`, it.jumlah_produksi);
                        addF(form, `komoditas[${i}][satuan]`,          it.satuan);
                        addF(form, `komoditas[${i}][bulan_produksi]`,  it.bulan_produksi);
                        addF(form, `komoditas[${i}][keterangan]`,      it.keterangan);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }

        function addF(form, name, value) {
            const f = document.createElement('input');
            f.type = 'hidden'; f.name = name; f.value = value || '';
            form.appendChild(f);
        }

        function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

        // Mic component — getter/setter pattern supaya bisa bind ke parent scope
        function micInput(getter, setter, isNumber = false) {
            return {
                isListening: false, rec: null,
                init() {
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) return;
                    this.rec = new SR();
                    this.rec.lang = 'id-ID';
                    this.rec.continuous = false;
                    this.rec.interimResults = false;
                    this.rec.onstart = () => this.isListening = true;
                    this.rec.onend   = () => this.isListening = false;
                    this.rec.onerror = () => this.isListening = false;
                    this.rec.onresult = (e) => {
                        let t = e.results[0][0].transcript;
                        t = this.w2n(t).replace(/\./g,'');
                        if (isNumber) {
                            const n = t.replace(/[^0-9.]/g,'');
                            setter(n || '');
                            if (this.$refs.el) { this.$refs.el.value = n || ''; }
                        } else {
                            const v = cap(t);
                            setter(v);
                            if (this.$refs.el) { this.$refs.el.value = v; }
                        }
                    };
                },
                w2n(text) {
                    const m = {'nol':0,'kosong':0,'satu':1,'dua':2,'tiga':3,'empat':4,'lima':5,'enam':6,'tujuh':7,'delapan':8,'sembilan':9,'sepuluh':10,'sebelas':11};
                    text = text.replace(/([a-z]+)\s+belas/gi,(x,p)=>m[p.toLowerCase()]?(m[p.toLowerCase()]+10).toString():x);
                    text = text.replace(/([a-z]+)\s+puluh(?:\s+([a-z]+))?/gi,(x,p1,p2)=>{let v=(m[p1.toLowerCase()]||0)*10;if(p2)v+=(m[p2.toLowerCase()]||0);return v>0?v.toString():x;});
                    for(let w in m)text=text.replace(new RegExp('\\b'+w+'\\b','gi'),m[w]);
                    return text;
                },
                toggle() {
                    if (!this.rec) { alert('Browser Anda tidak mendukung fitur Suara. Gunakan Google Chrome.'); return; }
                    this.isListening ? this.rec.stop() : this.rec.start();
                }
            };
        }
    </script>
</body>
</html>
