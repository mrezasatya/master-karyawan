<fieldset class="space-y-4">
    <legend class="mb-2 text-base font-semibold text-slate-900">Pekerjaan &amp; kontrak saat ini</legend>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-field :form="$form" name="jobtitle" label="Job title (Position di CV)" :value="$employee->jobtitle" required maxlength="150" />
        <x-field :form="$form" name="kode_jbt" label="Kode jabatan" :value="$employee->kode_jbt" maxlength="50" />
        <x-field :form="$form" name="kategori_karyawan" label="Kategori karyawan" :value="$employee->kategori_karyawan" maxlength="100" help="Kolom 'Status' pada Excel lama, mis. Karyawan Proyek." />
        <x-field :form="$form" name="status_awal" label="Status awal" :value="$employee->status_awal" maxlength="100" />
        <x-field :form="$form" name="tgl_masuk" type="date" label="Tanggal masuk" :value="$employee->tgl_masuk" required />
        <x-field :form="$form" name="kontrak_ke" label="Kontrak ke-" :value="$employee->kontrak_ke" maxlength="30" />
        <x-field :form="$form" name="akhir_kontrak" type="date" label="Akhir kontrak" :value="$employee->akhir_kontrak" help="Kontrak yang lewat tidak menonaktifkan karyawan secara otomatis." />
    </div>
</fieldset>
