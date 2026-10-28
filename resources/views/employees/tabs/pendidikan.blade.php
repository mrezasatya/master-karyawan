@php $levels = config('karyawan.options.education_level'); @endphp
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card p-4 sm:p-6 lg:col-span-2">
        <h2 class="mb-4 text-base font-semibold">Riwayat pendidikan</h2>
        <div class="space-y-3">
            @forelse ($employee->education as $ed)
                @php $f = 'edu-'.$ed->id; @endphp
                <div x-data="{ edit: {{ old('_form') === $f ? 'true' : 'false' }} }" class="rounded-lg border border-slate-200 p-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm">
                            <span class="font-semibold">{{ $ed->graduation_year ?? 'Tahun ?' }}</span> — {{ $ed->level }}{{ $ed->major ? ' - '.$ed->major : '' }} - {{ $ed->institution }}
                            @unless ($ed->graduation_year) <x-badge color="amber">tahun lulus belum diisi</x-badge> @endunless
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="edit = !edit">Edit</button>
                            <form method="POST" action="{{ route('education.destroy', $ed) }}" onsubmit="return confirm('Hapus riwayat pendidikan ini?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-red-700">Hapus</button></form>
                        </div>
                    </div>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('education.update', $ed) }}" class="mt-3 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <input type="hidden" name="_form" value="{{ $f }}">
                        @include('employees.partials.education-fields', ['f' => $f, 'ed' => $ed])
                        <div class="sm:col-span-2"><button class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada riwayat pendidikan.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('education.store', $employee) }}" class="mt-5 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="_form" value="edu-new">
            <div class="text-sm font-semibold sm:col-span-2">Tambah pendidikan</div>
            @include('employees.partials.education-fields', ['f' => 'edu-new', 'ed' => null])
            <div class="sm:col-span-2"><button class="btn btn-primary">Tambah</button></div>
        </form>
    </div>

    <div class="card p-4 sm:p-6">
        <h2 class="mb-1 text-base font-semibold">Bahasa</h2>
        <p class="mb-4 text-xs text-slate-500">Ditampilkan pada baris Language di CV.</p>
        <ul class="mb-4 space-y-2">
            @forelse ($employee->languages as $lang)
                <li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    {{ $lang->language_name }}
                    <form method="POST" action="{{ route('languages.destroy', $lang) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-red-700" aria-label="Hapus {{ $lang->language_name }}">Hapus</button></form>
                </li>
            @empty
                <li class="text-sm text-slate-500">Belum ada bahasa.</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('languages.store', $employee) }}" class="space-y-3" x-data="{ choice: '{{ form_value('lang', 'choice') }}' }">
            @csrf
            <input type="hidden" name="_form" value="lang">
            <x-select form="lang" name="choice" label="Tambah bahasa" :options="[...config('karyawan.options.languages'), 'Lainnya']" x-model="choice" />
            <div x-show="choice === 'Lainnya'" x-cloak><x-field form="lang" name="other" label="Nama bahasa" maxlength="100" /></div>
            <button class="btn btn-primary w-full">Tambah Bahasa</button>
        </form>
    </div>
</div>
