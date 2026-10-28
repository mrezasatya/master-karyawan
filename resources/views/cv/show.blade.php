<x-layouts.app :title="'CV '.$employee->nama">
    <div class="mb-2"><a href="{{ route('cv.index') }}" class="link text-sm">← CV Generator</a></div>
    <x-page-header :title="'CV — '.$employee->nama" :subtitle="'NIK '.$employee->nik">
        <x-slot:actions>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Lengkapi Data</a>
            @if ($check['ready'])
                <a href="{{ route('cv.pdf', $employee) }}" class="btn btn-primary">Unduh PDF</a>
            @else
                <button class="btn btn-primary" disabled title="Lengkapi data wajib">Unduh PDF</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
        <div class="space-y-4">
            <div class="card p-4">
                <h2 class="mb-3 text-sm font-semibold">Data wajib CV final</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($check['required'] as $label => $ok)
                        <li class="flex items-center gap-2">
                            <span class="{{ $ok ? 'text-green-600' : 'text-red-600' }}" aria-hidden="true">{{ $ok ? '✔' : '✖' }}</span>
                            <span class="{{ $ok ? '' : 'font-medium text-red-700' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
                @unless ($check['ready'])
                    <p class="mt-3 rounded-lg bg-red-50 p-2 text-xs text-red-700">Unduhan diblokir sampai data wajib lengkap. Sistem tidak mengisi nilai yang kosong.</p>
                @endunless
            </div>
            <div class="card p-4">
                <h2 class="mb-3 text-sm font-semibold">Peringatan kelengkapan</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($check['warnings'] as $label => $ok)
                        <li class="flex items-center gap-2">
                            <span class="{{ $ok ? 'text-green-600' : 'text-amber-600' }}" aria-hidden="true">{{ $ok ? '✔' : '!' }}</span>
                            <span class="{{ $ok ? '' : 'text-amber-800' }}">{{ $label }}{{ $ok ? '' : ' — belum ada' }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-slate-500">Tinjau peringatan sebelum mengunduh. Bagian yang kosong ditampilkan "-" pada CV.</p>
            </div>
            <div class="card p-4 text-xs text-slate-500">
                Data administrasi (KTP, BPJS, NPWP, KK, nama ibu, agama, golongan darah, tinggi/berat badan, status nikah, alamat, PTKP) tidak pernah dicetak pada CV.
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-2 text-sm font-semibold">Preview</div>
            <iframe src="{{ route('cv.preview', $employee) }}" class="h-[80vh] w-full bg-white" title="Preview CV"></iframe>
        </div>
    </div>
</x-layouts.app>
