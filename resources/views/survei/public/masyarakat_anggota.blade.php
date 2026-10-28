<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Anggota Keluarga</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .anggota-card {
            background: var(--surface-light);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
        }
        .btn-remove {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #FEE2E2;
            color: #DC2626;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.2s;
        }
        .btn-remove:hover {
            background: #FECACA;
        }
        .btn-add {
            width: 100%;
            background: transparent;
            border: 2px dashed var(--primary);
            color: var(--primary);
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 24px;
            transition: all 0.2s;
        }
        .btn-add:hover {
            background: var(--primary-light);
        }
        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="stepper">
        <div class="step-item" style="color: var(--success);">✓ Data Diri</div>
        <div class="step-arrow">→</div>
        <div class="step-item active" id="stepIndicator2">👨‍👩‍👧‍👦 Anggota Keluarga</div>
    </div>

    <div class="step-container">
        <div class="step show" id="step1">
            <div class="step-header" style="margin-bottom: 16px;">
                <h1 class="step-title">Data Anggota Keluarga</h1>
                <p class="step-subtitle">Masukkan usia dan jenis kelamin untuk setiap anggota keluarga di rumah Anda (termasuk Anda sendiri).</p>
            </div>

            @if ($errors->any())
                <div style="background: #FEE2E2; color: #DC2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem;">
                    Terdapat kesalahan, pastikan semua kolom terisi dengan benar.
                </div>
            @endif

            <form action="{{ route('survei.public.masyarakat.anggota.store', array_merge(['token' => $token, 'id_masyarakat' => $masyarakat->id], request()->query())) }}" method="POST" x-data="anggotaKeluarga()">
                @csrf
                
                <template x-for="(anggota, index) in daftarAnggota" :key="anggota.id">
                    <div class="anggota-card" x-transition>
                        <button type="button" class="btn-remove" @click="hapusAnggota(index)" x-show="daftarAnggota.length > 1" title="Hapus Anggota">×</button>
                        
                        <div style="margin-bottom: 16px; font-weight: 600; color: var(--text-main);">
                            Anggota Keluarga #<span x-text="index + 1"></span>
                        </div>

                        <div class="form-group">
                            <label>Nama (Boleh Nama Panggilan)</label>
                            <input type="text" x-model="anggota.nama" :name="`anggota[${index}][nama]`" placeholder="Contoh: Budi" required>
                        </div>
                        
                        <div class="row-2">
                            <div class="form-group">
                                <label>Umur (Tahun)</label>
                                <input type="number" x-model="anggota.umur" :name="`anggota[${index}][umur]`" min="0" max="150" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select x-model="anggota.jenis_kelamin" :name="`anggota[${index}][jenis_kelamin]`" required>
                                    <option value="">Pilih...</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" class="btn-add" @click="tambahAnggota()">
                    + Tambah Anggota Keluarga
                </button>

                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                    Simpan dan Lanjutkan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('anggotaKeluarga', () => {
        const existingData = @json($masyarakat->anggota);
        let initialData = [];
        
        if (existingData && existingData.length > 0) {
            initialData = existingData.map(a => ({
                id: a.id || Date.now() + Math.random(),
                nama: a.nama,
                umur: a.umur,
                jenis_kelamin: a.jenis_kelamin
            }));
        } else {
            initialData = [
                { 
                    id: Date.now(), 
                    nama: '{{ $masyarakat->nama_kepala_keluarga }}', 
                    umur: '{{ $masyarakat->umur ?? "" }}', 
                    jenis_kelamin: '{{ $masyarakat->jenis_kelamin ?? "" }}' 
                }
            ];
        }

        return {
            daftarAnggota: initialData,
            tambahAnggota() {
                this.daftarAnggota.push({
                    id: Date.now(),
                    nama: '',
                    umur: '',
                    jenis_kelamin: ''
                });
            },
            hapusAnggota(index) {
                if(this.daftarAnggota.length > 1) {
                    this.daftarAnggota.splice(index, 1);
                }
            }
        };
    });
});
</script>
</body>
</html>
