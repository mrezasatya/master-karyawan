@props(['title' => null])
@php
    $user = auth()->user();
    $nav = $user?->isHr() ? [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'employees.index', 'match' => 'employees.*', 'label' => 'Data Karyawan'],
        ['route' => 'projects.index', 'match' => 'projects.*', 'label' => 'Data Proyek'],
        ['route' => 'imports.index', 'match' => 'imports.*', 'label' => 'Import / Export'],
        ['route' => 'cv.index', 'match' => 'cv.*', 'label' => 'CV Generator'],
        ['route' => 'accounts.index', 'match' => 'accounts.*', 'label' => 'Akun Pengguna'],
        ['route' => 'audit.index', 'match' => 'audit.*', 'label' => 'Audit Log'],
    ] : [];
    if ($user?->employee_id) {
        $nav[] = ['route' => 'portal.index', 'match' => 'portal.*', 'label' => 'Profil Saya'];
    }
@endphp
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
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<div x-data="{ open: false }" class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform border-r border-slate-200 bg-white transition lg:static lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center border-b border-slate-200 px-5">
            <img src="{{ asset('images/logo-bukaka.png') }}" alt="Bukaka" class="h-6">
        </div>
        <nav class="space-y-1 p-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium {{ request()->routeIs($item['match']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button" @click="open = true" class="btn btn-ghost -ml-2 px-2 lg:hidden" aria-label="Buka menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <img src="{{ asset('images/logo-bukaka.png') }}" alt="Bukaka" class="h-5 lg:hidden">
                <span class="hidden text-sm font-semibold text-slate-500 sm:inline">Master Data Karyawan Proyek</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right leading-tight">
                    <div class="max-w-[10rem] truncate text-sm font-semibold sm:max-w-xs">{{ $user?->displayName() }}</div>
                    <div class="text-xs text-slate-500">{{ $user?->role }} · {{ $user?->username }}</div>
                </div>
                <form id="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-secondary btn-sm">Keluar</button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>

<div x-data="idleLogout({{ (int) config('karyawan.idle_timeout') }})" x-show="warning" x-cloak
     class="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-md rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-lg">
    Tidak ada aktivitas. Anda akan keluar otomatis dalam <strong x-text="Math.max(remaining, 0)"></strong> detik.
    Ketuk di mana saja untuk tetap masuk.
</div>
</body>
</html>
