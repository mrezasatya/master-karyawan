@php
    $rows = collect($a['rows']);
    $labels = array_flip(\App\Services\Excel\Columns::EMPLOYEE);
    $limit = 300;
@endphp
<x-layouts.app title="Preview Import">
    <div class="mb-2"><a href="{{ route('imports.index') }}" class="link text-sm">← Import / Export</a></div>
    <x-page-header title="2. Preview import" :subtitle="$batch->file_name.' · sheet '.$a['sheet']" />

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['Total baris', $a['summary']['total'], 'text-slate-900'],
            ['Profil baru', $a['summary']['new'], 'text-green-700'],
            ['Akan diperbarui', $a['summary']['update'], 'text-brand-700'],
            ['Tanpa perubahan', $a['summary']['unchanged'], 'text-slate-500'],
            ['NIK duplikat', $a['summary']['duplicate'], 'text-red-700'],
            ['Baris invalid', $a['summary']['invalid'], 'text-red-700'],
        ] as [$label, $value, $class])
            <div class="card p-3"><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-2xl font-bold tabular-nums {{ $class }}">{{ $value }}</div></div>
        @endforeach
    </div>

    @foreach ($a['notes'] as $note)
        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $note }}</div>
    @endforeach

    @if ($a['mapping'])
        <section class="card mb-6 p-4">
            <h2 class="mb-2 font-semibold text-amber-800">Nilai yang tidak dikenali (perlu pemetaan)</h2>
            <p class="mb-3 text-sm text-slate-600">Nilai berikut tidak diimpor (tidak dibuang diam-diam). Perbaiki di file, atau minta admin menambah pemetaan di <code>config/karyawan.php</code>, lalu unggah ulang.</p>
            <ul class="space-y-1 text-sm">
                @foreach ($a['mapping'] as $field => $values)
                    @foreach ($values as $value => $count)
                        <li><span class="font-medium">{{ $labels[$field] ?? $field }}</span>: "{{ $value }}" — {{ $count }} baris</li>
                    @endforeach
                @endforeach
            </ul>
        </section>
    @endif

    @php $problem = $rows->whereIn('status', ['invalid', 'duplicate']); @endphp
    @if ($problem->isNotEmpty())
        <section class="mb-6">
            <h2 class="mb-2 font-semibold text-red-700">Baris yang tidak akan diimpor ({{ $problem->count() }})</h2>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Masalah</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($problem->take($limit) as $r)
                        <tr class="bg-red-50/40"><td>{{ $r['row'] }}</td><td class="font-mono text-xs">{{ $r['nik'] ?? '-' }}</td><td>{{ $r['nama'] }}</td>
                            <td><ul class="list-disc pl-4 text-red-700">@foreach ($r['errors'] as $e)<li>{{ $e }}</li>@endforeach</ul></td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif

    @php $news = $rows->where('status', 'new'); @endphp
    @if ($news->isNotEmpty())
        <section class="mb-6">
            <h2 class="mb-2 font-semibold text-green-800">Profil baru ({{ $news->count() }})</h2>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Proyek</th><th>Peringatan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($news->take($limit) as $r)
                        <tr><td>{{ $r['row'] }}</td><td class="font-mono text-xs">{{ $r['nik'] }}</td><td>{{ $r['nama'] }}</td><td>{{ $r['data']['jobtitle'] ?? '' }}</td><td>{{ $r['project'] }}</td>
                            <td class="text-xs text-amber-800">@foreach ($r['warnings'] as $w)<div>• {{ $w }}</div>@endforeach</td></tr>
                    @endforeach
                </tbody>
            </table></div>
            @if ($news->count() > $limit)<p class="mt-2 text-xs text-slate-500">Menampilkan {{ $limit }} dari {{ $news->count() }} baris.</p>@endif
        </section>
    @endif

    @php $updates = $rows->where('status', 'update'); @endphp
    @if ($updates->isNotEmpty())
        <section class="mb-6">
            <h2 class="mb-2 font-semibold text-brand-800">Profil yang sudah ada ({{ $updates->count() }})</h2>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Perubahan</th><th>Peringatan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($updates->take($limit) as $r)
                        <tr><td>{{ $r['row'] }}</td><td class="font-mono text-xs">{{ $r['nik'] }}</td><td>{{ $r['nama'] }}</td>
                            <td class="text-xs">
                                @forelse ($r['important'] as $field => [$old, $new])
                                    <div><span class="font-medium">{{ $labels[$field] ?? $field }}</span>: <span class="text-red-700 line-through">{{ $old ?? '(kosong)' }}</span> → <span class="text-green-700">{{ $new }}</span></div>
                                @empty
                                @endforelse
                                @php $others = array_diff($r['changes'], array_keys($r['important'])); @endphp
                                @if ($others)<div class="text-slate-500">Field lain berubah: {{ collect($others)->map(fn ($f) => $labels[$f] ?? $f)->join(', ') }}</div>@endif
                                @if (! $r['changes'])<span class="text-slate-400">Tidak ada perubahan</span>@endif
                            </td>
                            <td class="text-xs text-amber-800">@foreach ($r['warnings'] as $w)<div>• {{ $w }}</div>@endforeach</td></tr>
                    @endforeach
                </tbody>
            </table></div>
            @if ($updates->count() > $limit)<p class="mt-2 text-xs text-slate-500">Menampilkan {{ $limit }} dari {{ $updates->count() }} baris.</p>@endif
        </section>
    @endif

    @php $badAssign = collect($a['assignments'])->filter(fn ($x) => $x['errors']); @endphp
    @if ($a['projects_new'] || $badAssign->isNotEmpty())
        <section class="card mb-6 p-4">
            <h2 class="mb-2 font-semibold">Penugasan proyek ({{ count($a['assignments']) }} baris)</h2>
            @if ($a['projects_new'])
                <p class="mb-2 text-sm text-slate-700">Proyek berikut <strong>belum ada</strong> di master proyek:</p>
                <ul class="mb-3 flex flex-wrap gap-2">@foreach ($a['projects_new'] as $p)<li><x-badge color="amber">{{ $p }}</x-badge></li>@endforeach</ul>
            @endif
            @foreach ($badAssign->take($limit) as $x)
                <div class="text-sm text-red-700">Sheet {{ $x['sheet'] }} baris {{ $x['row'] }} (NIK {{ $x['nik'] ?? '-' }}): {{ implode(' ', $x['errors']) }}</div>
            @endforeach
        </section>
    @endif

    <form method="POST" action="{{ route('imports.process', $batch) }}" class="card space-y-3 p-4 sm:p-6">
        @csrf
        <h2 class="font-semibold">3. Konfirmasi</h2>
        <p class="text-sm text-slate-600">{{ $a['summary']['new'] }} profil baru dan {{ $a['summary']['update'] }} profil akan disimpan. Baris invalid/duplikat dilewati dan tercantum pada laporan kesalahan. Data yang tidak ada di file tidak dihapus; sel kosong tidak menghapus data lama.</p>
        @if ($a['projects_new'])
            <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="create_projects" value="1" class="mt-0.5 h-5 w-5"> Buat {{ count($a['projects_new']) }} proyek baru di master proyek. (Bila tidak dicentang, penugasan ke proyek tersebut dilewati.)</label>
        @endif
        @if (config('karyawan.password_awal') === 'tanggal_lahir' && $a['summary']['new'] > 0)
            <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="create_accounts" value="1" class="mt-0.5 h-5 w-5"> Buat akun login untuk profil baru yang memiliki tanggal lahir (username = NIK, password awal = DDMMYY, wajib diganti saat login pertama).</label>
        @endif
        <label class="flex items-start gap-3 text-sm font-medium"><input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-5 w-5"> Saya sudah meninjau preview dan ingin menyimpan data yang valid.</label>
        @error('confirm')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="flex flex-col gap-2 sm:flex-row">
            <button class="btn btn-primary" @disabled($a['summary']['new'] + $a['summary']['update'] === 0)>Proses Import</button>
        </div>
    </form>
    <form method="POST" action="{{ route('imports.cancel', $batch) }}" class="mt-3">
        @csrf
        <button class="btn btn-ghost">Batalkan import ini</button>
    </form>
</x-layouts.app>
