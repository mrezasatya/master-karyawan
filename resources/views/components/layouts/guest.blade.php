@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — ' : '' }}Master Karyawan Bukaka</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-bukaka.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <img src="{{ asset('images/logo-bukaka.png') }}" alt="Bukaka" class="mx-auto h-9">
            <p class="mt-3 text-sm text-slate-500">Master Data Karyawan Proyek</p>
        </div>
        <div class="card p-6">
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</body>
</html>
