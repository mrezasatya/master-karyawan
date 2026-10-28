<x-layouts.app title="Data Proyek">
    <x-page-header title="Data Proyek" subtitle="Master proyek untuk penugasan karyawan." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama proyek…" class="input">
                <select name="status" class="input sm:w-40"><option value="">Semua status</option>@foreach (config('karyawan.options.project_status') as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
                <button class="btn btn-primary">Cari</button>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Kode</th><th>Nama proyek</th><th>Status</th><th>Penugasan aktif</th><th class="text-right">Aksi</th></tr></thead>
                    @forelse ($projects as $p)
                        @php $f = 'prj-'.$p->id; $confirm = session('needs_confirm') === $p->id; @endphp
                        <tbody x-data="{ edit: {{ old('_form') === $f || $confirm ? 'true' : 'false' }} }" class="border-t border-slate-100">
                            <tr>
                                <td class="font-mono text-xs">{{ $p->project_code ?? '-' }}</td>
                                <td><a href="{{ route('projects.show', $p) }}" class="link">{{ $p->project_name }}</a></td>
                                <td><x-status-badge :status="$p->status" /></td>
                                <td>{{ $p->active_count }}</td>
                                <td class="text-right"><button type="button" class="btn btn-ghost btn-sm" @click="edit = !edit">Edit</button></td>
                            </tr>
                            <tr x-show="edit" x-cloak class="bg-slate-50">
                                <td colspan="5">
                                    <form method="POST" action="{{ route('projects.update', $p) }}" class="grid gap-3 sm:grid-cols-4">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="_form" value="{{ $f }}">
                                        <x-field :form="$f" name="project_code" label="Kode" :value="$p->project_code" maxlength="50" />
                                        <x-field :form="$f" name="project_name" label="Nama" :value="$p->project_name" required maxlength="200" class="sm:col-span-2" />
                                        <x-select :form="$f" name="status" label="Status" :options="config('karyawan.options.project_status')" :value="$p->status" :placeholder="false" />
                                        @if ($confirm)
                                            <label class="flex items-center gap-2 text-sm text-amber-800 sm:col-span-4"><input type="checkbox" name="confirm_deactivate" value="1" class="h-5 w-5"> Saya mengerti masih ada penugasan aktif, tetap nonaktifkan proyek.</label>
                                        @endif
                                        <div class="sm:col-span-4"><button class="btn btn-primary btn-sm">Simpan</button></div>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody><tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada proyek.</td></tr></tbody>
                    @endforelse
                </table>
            </div>
            <div class="mt-4">{{ $projects->links() }}</div>
        </div>

        <form method="POST" action="{{ route('projects.store') }}" class="card h-fit space-y-3 p-4 sm:p-6">
            @csrf
            <input type="hidden" name="_form" value="prj-new">
            <h2 class="text-base font-semibold">Tambah proyek</h2>
            <x-field form="prj-new" name="project_code" label="Kode proyek" maxlength="50" help="Opsional, unik bila diisi." />
            <x-field form="prj-new" name="project_name" label="Nama proyek" required maxlength="200" />
            <x-select form="prj-new" name="status" label="Status" :options="config('karyawan.options.project_status')" value="Aktif" :placeholder="false" />
            <button class="btn btn-primary w-full">Simpan Proyek</button>
        </form>
    </div>
</x-layouts.app>
