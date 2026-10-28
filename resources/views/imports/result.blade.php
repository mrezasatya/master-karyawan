@php $s = $batch->error_summary ?? []; @endphp
<x-layouts.app title="Hasil Import">
    <div class="mb-2"><a href="{{ route('imports.index') }}" class="link text-sm">← Import / Export</a></div>
    <x-page-header title="Hasil import" :subtitle="$batch->file_name">
        <x-slot:actions>
            @if ($batch->status !== 'Dibatalkan')
                <a href="{{ route('imports.report', $batch) }}" class="btn btn-secondary">Unduh Laporan Kesalahan</a>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="card mb-6 grid grid-cols-2 gap-4 p-4 sm:grid-cols-5">
        <div><div class="text-xs text-slate-500">Status</div><div class="font-semibold">{{ $batch->status }}</div></div>
        <div><div class="text-xs text-slate-500">Total baris</div><div class="font-semibold">{{ $batch->total_rows }}</div></div>
        <div><div class="text-xs text-slate-500">Profil baru</div><div class="font-semibold text-green-700">{{ $batch->created_count }}</div></div>
        <div><div class="text-xs text-slate-500">Diperbarui</div><div class="font-semibold text-brand-700">{{ $batch->updated_count }}</div></div>
        <div><div class="text-xs text-slate-500">Gagal</div><div class="font-semibold text-red-700">{{ $batch->failed_count }}</div></div>
        <div class="col-span-2 text-xs text-slate-500 sm:col-span-5">Diunggah oleh {{ $batch->uploader?->displayName() }} · {{ \App\Support\Format::dateTime($batch->created_at) }} · Diproses {{ \App\Support\Format::dateTime($batch->processed_at) }}
            @if (! empty($s['accounts_created'])) · {{ $s['accounts_created'] }} akun dibuat @endif</div>
    </div>
    @if (! empty($s['fatal']))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $s['fatal'] }}</div>
    @endif
    @foreach ([['rows', 'Baris yang gagal', 'text-red-700'], ['assignments', 'Penugasan yang dilewati', 'text-red-700'], ['warnings', 'Peringatan', 'text-amber-800']] as [$key, $title, $class])
        @if (! empty($s[$key]))
            <section class="card mb-4 p-4">
                <h2 class="mb-2 font-semibold {{ $class }}">{{ $title }} ({{ count($s[$key]) }})</h2>
                <ul class="space-y-1 text-sm">
                    @foreach (array_slice($s[$key], 0, 200) as $item)
                        <li>Baris {{ $item['row'] }} · NIK {{ $item['nik'] ?? '-' }}: {{ implode(' ', $item['messages']) }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach
</x-layouts.app>
