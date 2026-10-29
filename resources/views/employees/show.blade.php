<x-layouts.app :title="$employee->nama">
    <div class="mb-2"><a href="{{ route('employees.index') }}" class="link text-sm">← Data Karyawan</a></div>
    <x-page-header :title="$employee->nama" :subtitle="'NIK '.$employee->nik.' · '.$employee->jobtitle">
        <x-slot:actions>
            <x-status-badge :status="$employee->status_karyawan" />
            <a href="{{ route('cv.show', $employee) }}" class="btn btn-secondary">Buat CV</a>
            <form method="POST" action="{{ route('employees.status', $employee) }}"
                  onsubmit="return confirm('{{ $employee->isActive() ? 'Nonaktifkan karyawan ini? Data tetap tersimpan dan akun login ikut dinonaktifkan.' : 'Aktifkan kembali karyawan ini?' }}')">
                @csrf
                <button class="btn {{ $employee->isActive() ? 'btn-danger' : 'btn-primary' }}">{{ $employee->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <nav class="-mx-4 mb-6 overflow-x-auto border-b border-slate-200 px-4 sm:mx-0 sm:px-0" aria-label="Bagian profil">
        <div class="flex min-w-max gap-1">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('employees.show', [$employee, 'tab' => $key]) }}"
                   class="flex min-h-11 items-center border-b-2 px-3 text-sm font-medium {{ $tab === $key ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>

    @include('employees.tabs.'.$tab)
</x-layouts.app>
