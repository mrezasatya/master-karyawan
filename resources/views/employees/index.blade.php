<x-layouts.app title="Data Karyawan">
    <x-page-header title="Data Karyawan" subtitle="{{ number_format($employees->total(), 0, ',', '.') }} profil ditemukan">
        <x-slot:actions>
            <a href="{{ route('imports.index') }}" class="btn btn-secondary">Import / Export Excel</a>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">+ Tambah Karyawan</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="label" for="q">Cari NIK / nama</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Ketik NIK atau nama…" type="search">
        </div>
        <x-select name="status" label="Status" :options="config('karyawan.options.status_karyawan')" :value="$filters['status'] ?? ''" placeholder="Semua" />
        <x-select name="project" label="Proyek aktif" :options="$projects->pluck('project_name', 'id')->all()" :value="$filters['project'] ?? ''" placeholder="Semua" />
        <x-select name="jobtitle" label="Jabatan" :options="$jobtitles->all()" :value="$filters['jobtitle'] ?? ''" placeholder="Semua" />
        <x-select name="kontrak" label="Akhir kontrak" :options="['30' => '≤ 30 hari', '60' => '≤ 60 hari', '90' => '≤ 90 hari', 'lewat' => 'Sudah lewat', 'kosong' => 'Tidak diisi']" :value="$filters['kontrak'] ?? ''" placeholder="Semua" />
        <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
            <button class="btn btn-primary">Terapkan</button>
            <a href="{{ route('employees.index') }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Proyek aktif</th><th>Status</th><th>Akhir kontrak</th><th class="text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $e)
                    <tr>
                        <td class="font-mono text-xs whitespace-nowrap">{{ $e->nik }}</td>
                        <td><a href="{{ route('employees.show', $e) }}" class="link">{{ $e->nama }}</a></td>
                        <td>{{ $e->jobtitle }}</td>
                        <td>{{ $e->activeProjects->pluck('project_name')->join(', ') ?: '-' }}</td>
                        <td><x-status-badge :status="$e->status_karyawan" /></td>
                        <td class="whitespace-nowrap">
                            {{ \App\Support\Format::date($e->akhir_kontrak) }}
                            @if ($e->isActive()) <div><x-contract-badge :employee="$e" /></div> @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('employees.show', $e) }}" class="btn btn-ghost btn-sm">Detail</a>
                            <a href="{{ route('cv.show', $e) }}" class="btn btn-ghost btn-sm">CV</a>
                            @if ($e->isActive())
                                <form method="POST" action="{{ route('employees.status', $e) }}" class="inline" onsubmit="return confirm('Nonaktifkan {{ addslashes($e->nama) }}? Data tetap tersimpan.')">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm text-red-700">Nonaktifkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">Tidak ada data yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-layouts.app>
