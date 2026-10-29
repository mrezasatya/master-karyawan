<div class="mx-auto max-w-3xl">
    <p class="mb-4 text-sm text-slate-500">Karyawan juga dapat menambah dan mengedit pengalamannya sendiri melalui menu Profil Saya. Semua perubahan tercatat (siapa dan kapan).</p>
    @php $openNew = old('_form') === 'exp-new'; @endphp
    <div x-data="{ add: {{ $openNew ? 'true' : 'false' }} }" class="mb-4">
        <button type="button" class="btn btn-primary" x-show="!add" @click="add = true">+ Tambah Pengalaman</button>
        <div x-show="add" x-cloak class="card p-4 sm:p-5">
            <h3 class="mb-4 font-semibold">Tambah pengalaman kerja</h3>
            @include('experiences._form', ['experience' => null, 'employeeId' => $employee->employee_id])
        </div>
    </div>
    @include('experiences._list', ['experiences' => $employee->experiences, 'employeeId' => $employee->employee_id])
</div>
