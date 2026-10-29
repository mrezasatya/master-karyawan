<x-layouts.guest title="Ganti Password">
    <h1 class="mb-1 text-lg font-bold">Ganti Password</h1>
    @if (auth()->user()->must_change_password)
        <p class="mb-5 text-sm text-slate-500">Demi keamanan, Anda wajib mengganti password awal sebelum melanjutkan.</p>
    @endif
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <x-field name="current_password" type="password" label="Password saat ini" required autocomplete="current-password" />
        <x-field name="password" type="password" label="Password baru" required autocomplete="new-password"
                 help="Minimal 8 karakter, mengandung huruf dan angka, tidak memuat tanggal lahir/NIK." />
        <x-field name="password_confirmation" type="password" label="Ulangi password baru" required autocomplete="new-password" />
        <button class="btn btn-primary w-full">Simpan Password</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button class="btn btn-ghost w-full">Keluar</button>
    </form>
</x-layouts.guest>
