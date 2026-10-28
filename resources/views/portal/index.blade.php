<x-layouts.app title="Profil Saya">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Profil Saya" />

        <div class="card mb-6 p-4 sm:p-5">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Nama</dt><dd class="font-semibold">{{ $employee->nama }}</dd></div>
                <div><dt class="text-slate-500">NIK</dt><dd class="font-mono">{{ $employee->nik }}</dd></div>
                <div><dt class="text-slate-500">Jabatan</dt><dd>{{ $employee->jobtitle }}</dd></div>
                <div><dt class="text-slate-500">Proyek aktif</dt><dd>{{ $employee->activeProjects->pluck('project_name')->join(', ') ?: '-' }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-slate-500">Data identitas, pendidikan, TRAINING, dan foto dikelola oleh HR pusat. Hubungi HR bila ada yang perlu diperbaiki.</p>
        </div>

        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Pengalaman Kerja</h2>
                <p class="text-xs text-slate-500">Terakhir diperbarui: {{ \App\Support\Format::dateTime($lastUpdate) }}</p>
            </div>
        </div>

        @php $openNew = session()->hasOldInput() && old('_form') === 'exp-new'; @endphp
        <div x-data="{ add: {{ $openNew || $experiences->isEmpty() ? 'true' : 'false' }} }" class="mb-4">
            <button type="button" class="btn btn-primary w-full sm:w-auto" x-show="!add" @click="add = true">+ Tambah Pengalaman</button>
            <div x-show="add" x-cloak class="card p-4 sm:p-5">
                <h3 class="mb-4 font-semibold">Tambah pengalaman kerja</h3>
                <p class="mb-4 text-xs text-slate-500">Isi pengalaman sebelum bergabung maupun selama bekerja di perusahaan. Data langsung tersimpan setelah Anda menekan Simpan.</p>
                @include('experiences._form', ['experience' => null])
            </div>
        </div>

        @include('experiences._list', ['experiences' => $experiences])
    </div>
</x-layouts.app>
