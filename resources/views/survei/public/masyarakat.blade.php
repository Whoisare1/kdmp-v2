<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lengkapi Data Survei Keluarga</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Load CSS from copied public/css/style.css -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script>
        window.APP_SUBMIT_URL = "{!! route('survei.public.masyarakat.store', array_merge(['token' => $token], request()->query())) !!}";
        window.WILAYAH_TREE = @json($wilayahTree);
    </script>
</head>
<body>

<div class="container">
    <div class="stepper">
        <div class="step-item active" id="stepIndicator1">① Data Diri</div>
        <div class="step-arrow">→</div>
        <div class="step-item" id="stepIndicator2">② Alamat</div>
        <div class="step-arrow">→</div>
        <div class="step-item" id="stepIndicator3">③ Lokasi</div>
    </div>

    <div class="step-container" id="stepContainer">
        
        <!-- Step 1: Data Diri -->
        <div class="step" id="step1">
            <div class="step-header">
                <h1 class="step-title">Data Diri</h1>
                <p class="step-subtitle">Yuk, mulai dengan beberapa data dasar.</p>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Responden</label>
                <input type="text" id="nama" placeholder="Contoh: Budi Santoso" value="{{ $masyarakat->nama_kepala_keluarga ?? '' }}" required>
            </div>
            <div class="form-group">
                <label for="nomor_hp">Nomor HP</label>
                <input type="tel" id="nomor_hp" placeholder="Contoh: 081234567890" pattern="^08[0-9]{8,11}$" value="{{ $masyarakat->nomor_hp ?? '' }}" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="umur">Umur (Tahun)</label>
                    <input type="number" id="umur" min="0" max="150" placeholder="Contoh: 45" value="{{ $masyarakat->umur ?? '' }}" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" required>
                        <option value="">Pilih...</option>
                        <option value="Laki-laki" {{ ($masyarakat?->jenis_kelamin == 'Laki-laki') ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ ($masyarakat?->jenis_kelamin == 'Perempuan') ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="nextStep(2)">Lanjutkan</button>
        </div>

        <!-- Step 2: Alamat Rumah -->
        <div class="step" id="step2">
            <div class="step-header">
                <h1 class="step-title">Alamat Tempat Tinggal</h1>
                <p class="step-subtitle">Lengkapi alamat tempat tinggal Anda agar data dapat tercatat dengan tepat.</p>
            </div>

            <!-- Bagian A: Wilayah -->
            <div class="address-card show" id="cardWilayah">
                <div class="form-group" id="fg-provinsi">
                    <label for="provinsi">Provinsi</label>
                    <select id="provinsi" onchange="showNextField('kabupaten')">
                        <option value="Jawa Tengah" selected>Jawa Tengah</option>
                    </select>
                </div>
                
                <div class="form-group hidden-field" id="fg-kabupaten">
                    <label for="kabupaten">Kabupaten/Kota</label>
                    <select id="kabupaten" onchange="loadKecamatan(); showNextField('kecamatan')">
                        <option value="">Pilih Kabupaten/Kota...</option>
                    </select>
                </div>

                <div class="form-group hidden-field" id="fg-kecamatan">
                    <label for="kecamatan">Kecamatan</label>
                    <select id="kecamatan" onchange="loadDesa(); showNextField('desa')">
                        <option value="">Pilih Kecamatan...</option>
                    </select>
                </div>

                <div class="form-group hidden-field" id="fg-desa">
                    <label for="desa">Desa/Kelurahan</label>
                    <select id="desa" onchange="wilayahSelesai()">
                        <option value="">Pilih Desa/Kelurahan...</option>
                    </select>
                </div>

                <div class="feedback-text hidden-element" id="fb-wilayah">
                    ✓ Wilayah sudah lengkap
                </div>
            </div>

            <!-- Bagian B: Detail Alamat -->
            <div class="address-card hidden-element" id="cardDetail">
                <h3 style="font-size: 1rem; color: var(--text-main); margin-bottom: 8px;">Sedikit lagi, lengkapi alamatnya</h3>
                
                <div class="form-group">
                    <label for="dusun">Dusun/Dukuh</label>
                    <input type="text" id="dusun" placeholder="Contoh: Kayuapu Kulon" value="{{ $masyarakat->dusun ?? '' }}">
                    <div class="helper-text">Isi jika wilayah Anda menggunakan Dusun/Dukuh.</div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label for="rt">RT</label>
                        <input type="text" id="rt" placeholder="01" value="{{ $masyarakat->rt ?? '' }}" oninput="checkDetailDone()">
                    </div>
                    <div class="form-group">
                        <label for="rw">RW</label>
                        <input type="text" id="rw" placeholder="02" value="{{ $masyarakat->rw ?? '' }}" oninput="checkDetailDone()">
                    </div>
                </div>

                <div class="feedback-text hidden-element" id="fb-rtrw">
                    ✓ Detail wilayah sudah lengkap
                </div>

                <div class="form-group" style="margin-top: 8px;">
                    <label for="nama_jalan">Nama Jalan (Opsional)</label>
                    <input type="text" id="nama_jalan" placeholder="Contoh: Jl. Lingkar Utara UMK" value="{{ $masyarakat->nama_jalan ?? '' }}">
                    <div class="helper-text">Isi jika mengetahui nama jalannya.</div>
                </div>

                <div class="form-group">
                    <label for="nomor_rumah">Nomor Rumah (Opsional)</label>
                    <input type="text" id="nomor_rumah" placeholder="Contoh: 2" value="{{ $masyarakat->nomor_rumah ?? '' }}">
                    <div class="helper-text">Isi jika ada atau jika mengetahui nomor rumah.</div>
                </div>

                <div class="form-group">
                    <label for="detail_alamat">Detail Alamat / Patokan</label>
                    <textarea id="detail_alamat" rows="2" placeholder="Contoh: dekat masjid, sebelah balai desa, rumah warna putih, Gang 3..." oninput="checkAlamatDone()">{{ $masyarakat->detail_alamat ?? '' }}</textarea>
                    <div class="helper-text">Tulis patokan yang membantu menunjukkan tempat tinggal Anda.</div>
                </div>

                <div class="feedback-text hidden-element" id="fb-alamat" style="margin-top: 8px;">
                    ✓ Alamat sudah dicatat
                </div>
                <p id="alamat-ready-text" class="hidden-element" style="font-size: 0.875rem; color: var(--text-muted); text-align: center; margin-top: 8px;">Tinggal satu langkah lagi untuk melengkapi data tempat tinggal Anda.</p>

                <button class="btn btn-primary hidden-element" id="btn-lanjut-alamat" onclick="prepareVerification()">Lanjutkan</button>
            </div>
            
            <div class="loading-state hidden-element" id="loadingAlamat">
                <div class="spinner"></div>
                <p style="font-weight: 500; font-size: 1rem;">Menyiapkan data alamat...</p>
            </div>
        </div>

        <!-- Step 3: Verifikasi Lokasi -->
        <div class="step" id="step3">
            <div class="step-header" style="text-align: center; margin-bottom: 16px;">
                <h1 class="step-title">Lengkapi Lokasi</h1>
                <p class="step-subtitle" style="font-weight: 500; color: var(--text-main);">Satu langkah terakhir 📍</p>
            </div>

            <div id="verifyIntro">
                <p style="text-align: center; font-size: 0.95rem; margin-bottom: 24px;">Untuk melengkapi data tempat tinggal, gunakan lokasi perangkat Anda saat ini. Pastikan Anda berada di lokasi tempat tinggal yang didaftarkan agar data lokasi dapat tercatat sesuai dengan alamat yang diberikan.</p>
                
                <button class="btn btn-primary" onclick="verifyLocation()">Gunakan Lokasi Saya</button>

                <div class="privacy-note">
                    <span style="font-size: 1rem;">🔒</span> Lokasi hanya digunakan saat proses ini dan tidak digunakan untuk pelacakan terus-menerus.
                </div>
            </div>

            <div class="loading-state hidden-element" id="loadingLokasi">
                <div class="spinner"></div>
                <p id="loadingLokasiText" style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);">📍 Mengambil lokasi...</p>
                <p id="loadingLokasiSub" style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">Mohon tunggu sebentar.</p>
            </div>

            <div class="result-box hidden-element" id="resultSuccess">
                <div class="icon-large" style="color: var(--success);">✓</div>
                <h2 style="color: var(--text-main); font-size: 1.25rem;">Data lokasi berhasil dicatat</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 16px;">Data tempat tinggal Anda sudah lengkap.</p>
                <button class="btn btn-primary" onclick="submitFinalData()">Lanjutkan Survei</button>
            </div>

            <div class="result-box hidden-element" id="resultError">
                <div class="icon-large" style="color: var(--error);">✕</div>
                <h2 style="color: var(--text-main); font-size: 1.25rem;">Lokasi belum dapat dicatat</h2>
                <p class="dynamic-message" style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 16px;">Lokasi perangkat belum memenuhi kondisi yang diperlukan untuk melengkapi data tempat tinggal. Silakan pastikan layanan lokasi perangkat aktif dan coba kembali saat berada di lokasi tempat tinggal.</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
                    <button class="btn btn-primary" onclick="retryVerification()">Coba Lagi</button>
                    <button class="btn" style="background-color: var(--surface-hover); color: var(--text-main);" onclick="lanjutkanTanpaLokasi(null)">Lanjutkan Survei</button>
                </div>
            </div>

            <div class="result-box hidden-element" id="resultOutside">
                <div class="icon-large" style="color: #F59E0B;">ℹ️</div>
                <h2 style="color: var(--text-main); font-size: 1.25rem;">Pemberitahuan Lokasi</h2>
                <p class="dynamic-message" style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 16px;">Lokasi perangkat berada di luar wilayah tempat tinggal yang dipilih. Anda tetap dapat melanjutkan pengisian survei. Jika ingin mencatat lokasi tempat tinggal, Anda dapat mencoba kembali saat berada di wilayah tersebut.</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
                    <button class="btn btn-primary" onclick="retryVerification()">Coba Lagi</button>
                    <button class="btn" style="background-color: var(--surface-hover); color: var(--text-main);" onclick="lanjutkanTanpaLokasi('DI_LUAR_WILAYAH')">Lanjutkan Survei</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// We need to override the submit logic in verification.js so it sends to our backend.
// In the original verification.js, submitFinalData or lanjutkanTanpaLokasi probably did window.location.reload()
// Since we don't want to modify verification.js directly (or maybe we do), we can override it here.

// Wilayah Tree Logic
document.addEventListener('DOMContentLoaded', function() {
    const kabSelect = document.getElementById('kabupaten');
    // Initialize Kabupaten
    window.WILAYAH_TREE.forEach(kab => {
        const opt = document.createElement('option');
        opt.value = kab.nama;
        opt.dataset.id = kab.id;
        opt.textContent = kab.nama;
        kabSelect.appendChild(opt);
    });
    
    // Auto-trigger next field for provinsi
    showNextField('kabupaten');

    // Pre-fill existing data if editing
    @if(isset($masyarakat))
        setTimeout(() => {
            const existingKab = '{{ $masyarakat->kabupaten }}';
            const existingKec = '{{ $masyarakat->kecamatan }}';
            const existingDesa = '{{ $masyarakat->desa }}';
            
            if (existingKab) {
                kabSelect.value = existingKab;
                loadKecamatan();
                showNextField('kecamatan');
                
                setTimeout(() => {
                    if (existingKec) {
                        document.getElementById('kecamatan').value = existingKec;
                        loadDesa();
                        showNextField('desa');
                        
                        setTimeout(() => {
                            if (existingDesa) {
                                document.getElementById('desa').value = existingDesa;
                                wilayahSelesai();
                            }
                        }, 100);
                    }
                }, 100);
            }
        }, 100);
    @endif
});

function loadKecamatan() {
    const kabSelect = document.getElementById('kabupaten');
    const kecSelect = document.getElementById('kecamatan');
    const selectedKabName = kabSelect.value;
    
    // Clear existing
    kecSelect.innerHTML = '<option value="">Pilih Kecamatan...</option>';
    document.getElementById('desa').innerHTML = '<option value="">Pilih Desa/Kelurahan...</option>';
    
    if (!selectedKabName) return;
    
    const kab = window.WILAYAH_TREE.find(k => k.nama === selectedKabName);
    if (kab && kab.anak) {
        kab.anak.sort((a, b) => a.nama.localeCompare(b.nama)).forEach(kec => {
            const opt = document.createElement('option');
            opt.value = kec.nama;
            opt.dataset.id = kec.id;
            opt.textContent = kec.nama;
            kecSelect.appendChild(opt);
        });
    }
}



function loadDesa() {
    const kabSelect = document.getElementById('kabupaten');
    const kecSelect = document.getElementById('kecamatan');
    const desaSelect = document.getElementById('desa');
    const selectedKabName = kabSelect.value;
    const selectedKecName = kecSelect.value;
    
    desaSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan...</option>';
    if (!selectedKecName) return;
    
    const kab = window.WILAYAH_TREE.find(k => k.nama === selectedKabName);
    if (kab && kab.anak) {
        const kec = kab.anak.find(k => k.nama === selectedKecName);
        if (kec && kec.anak) {
            kec.anak.sort((a, b) => a.nama.localeCompare(b.nama)).forEach(desa => {
                const opt = document.createElement('option');
                opt.value = desa.nama;
                opt.dataset.id = desa.id;
                opt.textContent = desa.nama;
                desaSelect.appendChild(opt);
            });
        }
    }
}

function sendToBackend(finalData) {
    fetch(window.APP_SUBMIT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(finalData)
    })
    .then(response => response.json())
    .then(data => {
        if(data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert('Gagal menyimpan data: ' + (data.message || 'Error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan jaringan.');
    });
}
</script>
<script src="{{ asset('js/verification.js') }}"></script>
</body>
</html>
