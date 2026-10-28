<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Survei Selesai - Terima Kasih</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .success-animation {
            animation: popIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: scale(0.8);
        }
        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="antialiased text-slate-800 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center success-animation">
    <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-6">
        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>
    
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 mb-4">Terima Kasih!</h1>
    <p class="text-slate-500 mb-8 leading-relaxed">
        Data survei Anda telah berhasil dikirim dan tersimpan di sistem kami. Partisipasi Anda sangat berarti untuk perencanaan dan pengembangan wilayah.
    </p>
    
    <a href="{{ route('survei.public.masyarakat.show', ['token' => $token]) }}" class="block w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-6 rounded-xl transition-colors mb-3">
        Isi Kuesioner Baru
    </a>
</div>

</body>
</html>
