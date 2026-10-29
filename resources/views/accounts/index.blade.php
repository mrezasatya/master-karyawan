<x-layouts.app title="Akun Pengguna">
    <x-page-header title="Akun Pengguna" subtitle="Username = NIK. Password tidak dapat dilihat; reset menghasilkan kredensial awal baru yang wajib diganti saat login." />

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('accounts.store') }}" class="card space-y-3 p-4 sm:p-5">
            @csrf
            <input type="hidden" name="_form" value="acc">
            <h2 class="font-semibold">Buat akun untuk karyawan</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field form="acc" name="nik" label="NIK karyawan" required maxlength="30" />
                <x-select form="acc" name="role" label="Role" :options="config('karyawan.options.role')" value="Karyawan" :placeholder="false" />
            </div>
            <p class="text-xs text-slate-500">
                @if (config('karyawan.password_awal') === 'tanggal_lahir')
                    Karyawan: password awal = tanggal lahir (DDMMYY); bila tanggal lahir kosong, sistem membuat password acak. HR: selalu password acak.
                @else
                    Password awal berupa password acak yang ditampilkan satu kali.
                @endif
            </p>
            <button class="btn btn-primary">Buat Akun</button>
        </form>

        @if (config('karyawan.password_awal') === 'tanggal_lahir')
            <form method="POST" action="{{ route('accounts.bulk') }}" class="card space-y-3 p-4 sm:p-5" onsubmit="return confirm('Buat akun untuk semua karyawan aktif yang belum punya akun?')">
                @csrf
                <h2 class="font-semibold">Buat akun massal</h2>
                <p class="text-sm text-slate-600"><strong>{{ number_format($withoutAccount, 0, ',', '.') }}</strong> karyawan aktif belum memiliki akun.
                    @if ($withoutAccountNoDob) {{ $withoutAccountNoDob }} di antaranya belum memiliki tanggal lahir dan akan dilewati (buat satu per satu dengan password acak). @endif
                </p>
                <button class="btn btn-secondary" @disabled($withoutAccount - $withoutAccountNoDob <= 0)>Buat Akun Massal</button>
            </form>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari NIK / nama…" class="input">
        <select name="role" class="input sm:w-36"><option value="">Semua role</option>@foreach (config('karyawan.options.role') as $r)<option @selected(request('role') === $r)>{{ $r }}</option>@endforeach</select>
        <select name="status" class="input sm:w-36"><option value="">Semua status</option>@foreach (config('karyawan.options.account_status') as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
        <button class="btn btn-primary">Cari</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th>Login terakhir</th><th>Password</th><th class="text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($accounts as $a)
                    <tr>
                        <td class="font-mono text-xs">{{ $a->username }}</td>
                        <td>{{ $a->displayName() }}</td>
                        <td>{{ $a->role }}</td>
                        <td><x-status-badge :status="$a->account_status" /></td>
                        <td class="whitespace-nowrap">{{ \App\Support\Format::dateTime($a->last_login_at) }}</td>
                        <td>@if ($a->must_change_password)<x-badge color="amber">belum diganti</x-badge>@else<x-badge color="green">sudah diganti</x-badge>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('accounts.reset', $a) }}" class="inline" onsubmit="return confirm('Reset password {{ $a->username }}?')">@csrf<button class="btn btn-ghost btn-sm">Reset password</button></form>
                            @if ($a->id !== auth()->id())
                                <form method="POST" action="{{ route('accounts.status', $a) }}" class="inline">@csrf<button class="btn btn-ghost btn-sm {{ $a->isActive() ? 'text-red-700' : '' }}">{{ $a->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $accounts->links() }}</div>
</x-layouts.app>
