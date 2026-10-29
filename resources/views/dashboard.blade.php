<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard HR" :subtitle="'Per '.\App\Support\Format::date(now())" />

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Karyawan aktif', $activeCount, route('employees.index', ['status' => 'Aktif'])],
            ['Karyawan nonaktif', $inactiveCount, route('employees.index', ['status' => 'Nonaktif'])],
            ['Proyek aktif', $activeProjects, route('projects.index', ['status' => 'Aktif'])],
            ['Kontrak ≤ 90 hari / lewat', $contracts->count(), '#kontrak'],
        ] as [$label, $value, $href])
            <a href="{{ $href }}" class="card block p-4 hover:border-brand-500">
                <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($value, 0, ',', '.') }}</div>
            </a>
        @endforeach
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Data Karyawan</a>
        <a href="{{ route('imports.index') }}" class="btn btn-secondary">Import Excel</a>
        <a href="{{ route('cv.index') }}" class="btn btn-secondary">CV Generator</a>
    </div>

    <section id="kontrak" class="card p-4 sm:p-6">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-semibold">Pengingat akhir kontrak</h2>
            <div class="flex flex-wrap gap-2 text-xs">
                <x-badge color="red">Lewat: {{ $buckets['lewat'] }}</x-badge>
                <x-badge color="orange">≤ 30 hari: {{ $buckets['30'] }}</x-badge>
                <x-badge color="amber">31–60 hari: {{ $buckets['60'] }}</x-badge>
                <x-badge color="blue">61–90 hari: {{ $buckets['90'] }}</x-badge>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nama</th><th>NIK</th><th>Proyek</th><th>Akhir kontrak</th><th class="text-right">Sisa hari</th><th>Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($contracts as $e)
                        <tr>
                            <td><a href="{{ route('employees.show', [$e, 'tab' => 'pekerjaan']) }}" class="link">{{ $e->nama }}</a></td>
                            <td class="font-mono text-xs">{{ $e->nik }}</td>
                            <td>{{ $e->activeProjects->pluck('project_name')->join(', ') ?: '-' }}</td>
                            <td class="whitespace-nowrap">{{ \App\Support\Format::date($e->akhir_kontrak) }}</td>
                            <td class="text-right tabular-nums">{{ $e->contractDaysLeft() }}</td>
                            <td><x-contract-badge :employee="$e" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada kontrak yang berakhir dalam 90 hari ke depan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-slate-500">Kontrak yang sudah lewat tetap ditampilkan selama karyawan masih aktif. Status karyawan tidak berubah otomatis.</p>
    </section>
</x-layouts.app>
