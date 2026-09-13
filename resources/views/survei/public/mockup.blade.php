<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Mockup – {{ $sesi->wilayah->nama ?? 'Desa' }}</title>
    <link rel="stylesheet" href="{{ asset('css/survey_mockup.css') }}">
    <style>
        /* Simple JS‑free tab switching using :target */
        .tab-content { display: none; }
        .tab-content:target { display: block; }
        .tab-content:first-of-type { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Survey Pemerintah Desa</h1>
            <div class="progress">
                <div class="filled" style="width: 33%;"></div>
            </div>
        </header>
        <nav class="tabs">
            <a href="#demografi" class="tab" id="tab-demografi">Demografi</a>
            <a href="#produksi" class="tab" id="tab-produksi">Potensi Produksi</a>
            <a href="#harga" class="tab" id="tab-harga">Harga Pasar</a>
        </nav>
        <section class="content">
            <div id="demografi" class="tab-content">
                <h2>Data Demografi</h2>
                <div class="form-section">
                    <div>
                        <label for="kk">Jumlah KK</label>
                        <input type="number" id="kk" name="kk" placeholder="contoh: 120" />
                    </div>
                    <div>
                        <label for="penduduk_total">Total Penduduk</label>
                        <input type="number" id="penduduk_total" name="penduduk_total" placeholder="contoh: 560" />
                    </div>
                </div>
                <div class="form-section">
                    <div>
                        <label for="balita">Balita</label>
                        <input type="number" id="balita" name="balita" />
                    </div>
                    <div>
                        <label for="anak">Anak</label>
                        <input type="number" id="anak" name="anak" />
                    </div>
                </div>
                <div class="form-section">
                    <div>
                        <label for="remaja">Remaja</label>
                        <input type="number" id="remaja" name="remaja" />
                    </div>
                    <div>
                        <label for="dewasa">Dewasa</label>
                        <input type="number" id="dewasa" name="dewasa" />
                    </div>
                </div>
                <div class="form-section">
                    <div>
                        <label for="lansia">Lansia</label>
                        <input type="number" id="lansia" name="lansia" />
                    </div>
                </div>
            </div>
            <div id="produksi" class="tab-content">
                <h2>Potensi Produksi</h2>
                <p>Petugas dapat menambah baris dengan menekan tombol <strong>+ Tambah Komoditas</strong>. Setiap baris terdiri dari:</p>
                <ul>
                    <li>Komoditas (pilihan dari master <code>komoditas</code>)</li>
                    <li>Jumlah produksi (satuan yang ditentukan di master)</li>
                    <li>Jumlah konsumsi</li>
                    <li>Jumlah dijual</li>
                </ul>
                <button class="button" style="margin-bottom:1rem;">+ Tambah Komoditas</button>
                <!-- Placeholder table -->
                <table class="form-section" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:var(--secondary-bg);color:var(--text-primary);">
                            <th style="padding:0.5rem;">Komoditas</th>
                            <th style="padding:0.5rem;">Jumlah</th>
                            <th style="padding:0.5rem;">Konsumsi</th>
                            <th style="padding:0.5rem;">Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:var(--card-bg);color:var(--text-primary);">
                            <td style="padding:0.5rem;">Beras</td>
                            <td style="padding:0.5rem;"><input type="text" placeholder="kg"/></td>
                            <td style="padding:0.5rem;"><input type="text" placeholder="kg"/></td>
                            <td style="padding:0.5rem;"><input type="text" placeholder="kg"/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="harga" class="tab-content">
                <h2>Harga Pasar</h2>
                <p>Input harga pasar per satuan standar (mis. per kg atau per ekor).</p>
                <div class="form-section">
                    <div>
                        <label for="komoditas_harga">Komoditas</label>
                        <select id="komoditas_harga" name="komoditas_harga">
                            <option value="">-- Pilih Komoditas --</option>
                        </select>
                    </div>
                    <div>
                        <label for="harga">Harga (Rp)</label>
                        <input type="number" id="harga" name="harga" placeholder="contoh: 12000" />
                    </div>
                </div>
            </div>
        </section>
        <footer class="footer">
            <button class="button" type="submit">Simpan & Lanjut</button>
        </footer>
    </div>
</body>
</html>
