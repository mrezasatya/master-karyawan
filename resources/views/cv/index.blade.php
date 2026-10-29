<x-layouts.app title="CV Generator">
    <x-page-header title="CV Generator" subtitle="Cari karyawan → periksa kelengkapan → preview → unduh PDF." />
    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari NIK atau nama…" class="input" autofocus>
        <select name="status" class="input sm:w-40"><option value="">Semua status</option>@foreach (config('karyawan.options.status_karyawan') as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
        <button class="btn btn-primary">Cari</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Proyek aktif</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $e)
                    <tr>
                        <td class="font-mono text-xs">{{ $e->nik }}</td>
                        <td>{{ $e->nama }}</td>
                        <td>{{ $e->jobtitle }}</td>
                        <td>{{ $e->activeProjects->pluck('project_name')->join(', ') ?: '-' }}</td>
                        <td><x-status-badge :status="$e->status_karyawan" /></td>
                        <td class="text-right"><a href="{{ route('cv.show', $e) }}" class="btn btn-primary btn-sm">Buka CV</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada karyawan ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-layouts.app>
