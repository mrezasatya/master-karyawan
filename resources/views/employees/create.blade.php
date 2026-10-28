<x-layouts.app title="Tambah Karyawan">
    <x-page-header title="Tambah Karyawan" subtitle="Isi data utama. Pendidikan, foto, TRAINING, dan proyek dapat dilengkapi setelah profil tersimpan." />
    <form method="POST" action="{{ route('employees.store') }}" x-data="dirtyForm" class="card space-y-8 p-4 sm:p-6">
        @csrf
        <input type="hidden" name="_form" value="profil">
        @include('employees.partials.form-identitas', ['form' => 'profil'])
        @include('employees.partials.form-pekerjaan', ['form' => 'profil'])
        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary">Simpan Profil</button>
        </div>
    </form>
</x-layouts.app>
