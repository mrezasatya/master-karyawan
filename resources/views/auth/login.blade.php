<x-layouts.guest title="Masuk">
    <h1 class="mb-1 text-lg font-bold">Masuk</h1>
    <p class="mb-5 text-sm text-slate-500">Gunakan NIK sebagai username.</p>
    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ show: false }">
        @csrf
        <x-field name="username" label="Username / NIK" :value="old('username')" required autocomplete="username" inputmode="text" autofocus />
        <div>
            <label for="password" class="label">Password <span class="text-red-600">*</span></label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" class="input pr-20">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-sm font-medium text-brand-700" x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</button>
            </div>
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary w-full">Masuk</button>
        <p class="text-center text-xs text-slate-500">Lupa password? Hubungi HR pusat untuk reset.<br>Selalu tekan <strong>Keluar</strong> setelah selesai bila memakai komputer bersama.</p>
    </form>
</x-layouts.guest>
