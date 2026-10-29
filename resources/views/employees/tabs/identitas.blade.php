<form method="POST" action="{{ route('employees.update', $employee) }}" x-data="dirtyForm" class="card p-4 sm:p-6">
    @csrf @method('PUT')
    <input type="hidden" name="_form" value="identitas">
    <input type="hidden" name="section" value="identitas">
    @include('employees.partials.form-identitas', ['form' => 'identitas'])
    <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
        <a href="{{ route('employees.show', [$employee, 'tab' => 'identitas']) }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan</button>
    </div>
    <p class="mt-3 text-xs text-slate-400">Dibuat {{ \App\Support\Format::dateTime($employee->created_at) }} · Diperbarui {{ \App\Support\Format::dateTime($employee->updated_at) }}</p>
</form>
