<form method="POST" action="{{ route('employees.update', $employee) }}" x-data="dirtyForm" class="card mb-6 p-4 sm:p-6">
    @csrf @method('PUT')
    <input type="hidden" name="_form" value="pekerjaan">
    <input type="hidden" name="section" value="pekerjaan">
    @include('employees.partials.form-pekerjaan', ['form' => 'pekerjaan'])
    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
        <span class="text-slate-500">Status pengingat kontrak:</span>
        <x-contract-badge :employee="$employee" />
        @if ($employee->contractDaysLeft() !== null)
            <span class="text-slate-500">({{ $employee->contractDaysLeft() }} hari)</span>
        @endif
    </div>
    <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
        <a href="{{ route('employees.show', [$employee, 'tab' => 'pekerjaan']) }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan</button>
    </div>
</form>

<div class="card p-4 sm:p-6">
    <h2 class="mb-1 text-base font-semibold">Penugasan proyek</h2>
    <p class="mb-4 text-xs text-slate-500">Satu karyawan dapat memiliki lebih dari satu proyek aktif. Proyek dipilih dari master proyek.</p>
    @php $statuses = config('karyawan.options.assignment_status'); $projectOptions = $projects->mapWithKeys(fn ($p) => [$p->id => $p->label()])->all(); @endphp
    <div class="space-y-3">
        @forelse ($employee->assignments as $a)
            @php $f = 'asg-'.$a->id; $opts = $projectOptions + [$a->project_id => $a->project->label().($a->project->status !== 'Aktif' ? ' (nonaktif)' : '')]; @endphp
            <div x-data="{ edit: {{ old('_form') === $f ? 'true' : 'false' }} }" class="rounded-lg border border-slate-200 p-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-medium">{{ $a->project->project_name }} <x-status-badge :status="$a->assignment_status" /></div>
                        <div class="text-xs text-slate-500">{{ \App\Support\Format::date($a->assignment_start) }} s/d {{ $a->assignment_end ? \App\Support\Format::date($a->assignment_end) : 'sekarang' }}</div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" @click="edit = !edit">Edit</button>
                        <form method="POST" action="{{ route('assignments.destroy', $a) }}" onsubmit="return confirm('Hapus penugasan ini? Sebaiknya ubah status menjadi Selesai agar riwayat tetap ada.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-red-700">Hapus</button>
                        </form>
                    </div>
                </div>
                <form x-show="edit" x-cloak method="POST" action="{{ route('assignments.update', $a) }}" class="mt-3 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="{{ $f }}">
                    <x-select :form="$f" name="project_id" label="Proyek" :options="$opts" :value="$a->project_id" required class="sm:col-span-4" />
                    <x-field :form="$f" name="assignment_start" type="date" label="Mulai" :value="$a->assignment_start" />
                    <x-field :form="$f" name="assignment_end" type="date" label="Selesai" :value="$a->assignment_end" />
                    <x-select :form="$f" name="assignment_status" label="Status" :options="$statuses" :value="$a->assignment_status" required :placeholder="false" />
                    <div class="flex items-end"><button class="btn btn-primary w-full">Simpan</button></div>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada penugasan proyek.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('assignments.store', $employee) }}" class="mt-5 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-4">
        @csrf
        <input type="hidden" name="_form" value="asg-new">
        <div class="text-sm font-semibold sm:col-span-4">Tambah penugasan</div>
        <x-select form="asg-new" name="project_id" label="Proyek" :options="$projectOptions" required class="sm:col-span-4"
                  help="Proyek belum ada? Tambahkan dulu di menu Data Proyek." />
        <x-field form="asg-new" name="assignment_start" type="date" label="Mulai" />
        <x-field form="asg-new" name="assignment_end" type="date" label="Selesai" />
        <x-select form="asg-new" name="assignment_status" label="Status" :options="$statuses" value="Aktif" required :placeholder="false" />
        <div class="flex items-end"><button class="btn btn-primary w-full">Tambah</button></div>
    </form>
</div>
