<div class="card p-4 sm:p-6">
    <h2 class="mb-1 text-base font-semibold">TRAINING</h2>
    <p class="mb-4 text-xs text-slate-500">Pelatihan, sertifikasi, dan kompetensi ditampilkan bersama pada bagian TRAINING di CV (kolom TRAINING, TAHUN, PENYELENGGARA). Jenis, nomor sertifikat, dan masa berlaku adalah metadata internal.</p>
    <div class="space-y-3">
        @forelse ($employee->trainings as $t)
            @php $f = 'trn-'.$t->id; @endphp
            <div x-data="{ edit: {{ old('_form') === $f ? 'true' : 'false' }} }" class="rounded-lg border border-slate-200 p-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm">
                        <div class="font-semibold">{{ $t->item_name }} <x-badge>{{ $t->item_type }}</x-badge></div>
                        <div class="text-slate-500">{{ $t->year ?? 'Tahun ?' }} · {{ $t->organizer ?? 'Penyelenggara belum diisi' }}
                            @if ($t->expiry_date) · berlaku s/d {{ \App\Support\Format::date($t->expiry_date) }} @if ($t->expiry_date->isPast()) <x-badge color="red">kedaluwarsa</x-badge> @endif @endif
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" @click="edit = !edit">Edit</button>
                        <form method="POST" action="{{ route('trainings.destroy', $t) }}" onsubmit="return confirm('Hapus data TRAINING ini?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-red-700">Hapus</button></form>
                    </div>
                </div>
                <form x-show="edit" x-cloak method="POST" action="{{ route('trainings.update', $t) }}" class="mt-3 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-3">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="{{ $f }}">
                    @include('employees.partials.training-fields', ['f' => $f, 't' => $t])
                    <div class="sm:col-span-3"><button class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada data TRAINING.</p>
        @endforelse
    </div>
    <form method="POST" action="{{ route('trainings.store', $employee) }}" class="mt-5 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-3">
        @csrf
        <input type="hidden" name="_form" value="trn-new">
        <div class="text-sm font-semibold sm:col-span-3">Tambah TRAINING</div>
        @include('employees.partials.training-fields', ['f' => 'trn-new', 't' => null])
        <div class="sm:col-span-3"><button class="btn btn-primary">Tambah</button></div>
    </form>
</div>
