{{-- Form pengalaman kerja (dipakai portal karyawan & HR). $experience null = tambah baru. --}}
@php
    $exp = $experience ?? null;
    $form = $exp ? 'exp-'.$exp->id : 'exp-new';
    $months = \App\Support\Format::MONTHS;
    $val = fn ($field, $default = null) => form_value($form, $field, $exp?->{$field} ?? $default);
    $isCurrent = (bool) (session()->hasOldInput() && old('_form') === $form ? old('is_current') : ($exp?->is_current ?? false));
@endphp
<form method="POST" action="{{ $exp ? route('experiences.update', $exp) : route('experiences.store') }}"
      x-data="ajaxForm()" @submit.prevent="submit" class="space-y-4" novalidate>
    @csrf
    @if ($exp) @method('PUT') @endif
    <input type="hidden" name="_form" value="{{ $form }}">
    @if (! empty($employeeId)) <input type="hidden" name="employee_id" value="{{ $employeeId }}"> @endif

    <div x-data="{ current: {{ $isCurrent ? 'true' : 'false' }} }" class="grid gap-4 sm:grid-cols-2">
        <x-select :form="$form" name="experience_type" label="Jenis pengalaman" :options="config('karyawan.options.experience_type')" :value="$val('experience_type')" required class="sm:col-span-2" />
        <div><x-field :form="$form" name="position" label="Jabatan / posisi" :value="$val('position')" required maxlength="150" /><p class="mt-1 text-sm text-red-600" x-text="error('position')"></p></div>
        <div><x-field :form="$form" name="company_name" label="Nama perusahaan" :value="$val('company_name')" required maxlength="200" list="company-list" /><p class="mt-1 text-sm text-red-600" x-text="error('company_name')"></p></div>
        <div class="sm:col-span-2"><x-field :form="$form" name="project_name" label="Nama proyek (opsional)" :value="$val('project_name')" maxlength="200" /><p class="mt-1 text-sm text-red-600" x-text="error('project_name')"></p></div>

        <fieldset class="sm:col-span-2">
            <legend class="label">Mulai <span class="text-red-600">*</span></legend>
            <div class="grid grid-cols-2 gap-3">
                <x-select :form="$form" name="start_month" :options="$months" :value="$val('start_month')" placeholder="Bulan" aria-label="Bulan mulai" />
                <x-field :form="$form" name="start_year" type="number" :value="$val('start_year')" placeholder="Tahun" inputmode="numeric" min="1950" max="{{ now()->year + 5 }}" aria-label="Tahun mulai" />
            </div>
            <p class="mt-1 text-sm text-red-600" x-text="error('start_month') || error('start_year')"></p>
        </fieldset>

        <label class="flex min-h-11 items-center gap-3 sm:col-span-2">
            <input type="checkbox" name="is_current" value="1" x-model="current" class="h-5 w-5 rounded border-slate-300 text-brand-600" @checked($isCurrent)>
            <span class="text-sm font-medium">Masih bekerja di sini</span>
        </label>

        <fieldset class="sm:col-span-2" x-show="!current">
            <legend class="label">Selesai <span class="text-red-600">*</span></legend>
            <div class="grid grid-cols-2 gap-3">
                <x-select :form="$form" name="end_month" :options="$months" :value="$val('end_month')" placeholder="Bulan" x-bind:disabled="current" aria-label="Bulan selesai" />
                <x-field :form="$form" name="end_year" type="number" :value="$val('end_year')" placeholder="Tahun" inputmode="numeric" min="1950" max="{{ now()->year + 5 }}" x-bind:disabled="current" aria-label="Tahun selesai" />
            </div>
            <p class="mt-1 text-sm text-red-600" x-text="error('end_month') || error('end_year')"></p>
        </fieldset>

        <div class="sm:col-span-2" x-data="{ len: {{ mb_strlen((string) $val('responsibilities')) }} }">
            <x-field :form="$form" name="responsibilities" type="textarea" rows="5" label="Uraian tanggung jawab" :value="$val('responsibilities')" maxlength="2000" x-on:input="len = $event.target.value.length" placeholder="Contoh: Mengawasi pekerjaan pemasangan struktur baja, memastikan K3 di lapangan…" />
            <p class="mt-1 flex justify-between text-xs text-slate-500"><span x-text="error('responsibilities')" class="text-red-600"></span><span><span x-text="len"></span>/2000</span></p>
        </div>

        @php $serverYearError = form_error($form, 'start_year') || form_error($form, 'end_year'); @endphp
        <label class="flex items-start gap-3 text-sm sm:col-span-2" x-show="{{ $serverYearError ? 'true' : 'false' }} || error('start_year') || error('end_year')" @unless ($serverYearError) x-cloak @endunless>
            <input type="checkbox" name="confirm_future" value="1" class="mt-0.5 h-5 w-5 rounded border-slate-300">
            <span>Saya konfirmasi tahun di masa depan memang benar.</span>
        </label>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-h-5 text-sm" role="status" aria-live="polite">
            <span x-show="state === 'saving' || state === 'retrying'" class="text-brand-700" x-text="message"></span>
            <span x-show="state === 'saved'" class="font-medium text-green-700" x-text="message"></span>
            <span x-show="state === 'failed' || state === 'invalid'" class="font-medium text-red-700" x-text="message"></span>
        </div>
        <button class="btn btn-primary w-full sm:w-auto" :disabled="state === 'saving' || state === 'retrying'">
            {{ $exp ? 'Simpan Perubahan' : 'Simpan Pengalaman' }}
        </button>
    </div>
</form>
