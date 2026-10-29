{{-- Daftar pengalaman kerja dengan form edit inline. Variabel: $experiences, $employeeId (opsional, untuk HR) --}}
<div class="space-y-3">
    @forelse ($experiences as $exp)
        @php $editing = session()->hasOldInput() && old('_form') === 'exp-'.$exp->id; @endphp
        <div x-data="{ edit: {{ $editing ? 'true' : 'false' }} }" class="card p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="text-xs font-medium text-slate-500">{{ $exp->periodLabel() }} · {{ $exp->experience_type }}</div>
                    <div class="font-semibold text-slate-900">{{ $exp->position }}</div>
                    <div class="text-sm text-slate-600">{{ $exp->company_name }}@if ($exp->project_name) — {{ $exp->project_name }}@endif</div>
                    @if ($exp->responsibilities)
                        <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $exp->responsibilities }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">Diperbarui {{ \App\Support\Format::dateTime($exp->updated_at) }}@if ($exp->updater) oleh {{ $exp->updater->displayName() }}@endif</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" @click="edit = !edit" x-text="edit ? 'Tutup' : 'Edit'">Edit</button>
                    <form method="POST" action="{{ route('experiences.destroy', $exp) }}" onsubmit="return confirm('Arsipkan pengalaman ini? Data tidak tampil lagi di CV.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm text-red-700">Arsipkan</button>
                    </form>
                </div>
            </div>
            <div x-show="edit" x-cloak class="mt-4 border-t border-slate-100 pt-4">
                @include('experiences._form', ['experience' => $exp, 'employeeId' => $employeeId ?? null])
            </div>
        </div>
    @empty
        <div class="card p-6 text-center text-sm text-slate-500">Belum ada pengalaman kerja yang dicatat.</div>
    @endforelse
</div>
