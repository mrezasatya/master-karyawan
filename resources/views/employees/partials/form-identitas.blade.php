@php $o = config('karyawan.options'); @endphp
<fieldset class="space-y-4">
    <legend class="mb-2 text-base font-semibold text-slate-900">Data personal</legend>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-field :form="$form" name="nik" label="NIK" :value="$employee->nik" required maxlength="30" help="Disimpan sebagai teks; angka nol di depan dipertahankan." />
        <x-field :form="$form" name="nama" label="Nama lengkap" :value="$employee->nama" required maxlength="150" class="lg:col-span-2" />
        <x-field :form="$form" name="nama_panggilan" label="Nama panggilan" :value="$employee->nama_panggilan" maxlength="100" />
        <x-select :form="$form" name="jenis_kelamin" label="Jenis kelamin" :options="$o['jenis_kelamin']" :value="$employee->jenis_kelamin" required />
        <x-field :form="$form" name="kewarganegaraan" label="Kewarganegaraan" :value="$employee->kewarganegaraan" maxlength="100" help="Wajib untuk CV (Citizen). Contoh: Indonesian." list="citizen-list" />
        <x-field :form="$form" name="tempat_lahir" label="Tempat lahir" :value="$employee->tempat_lahir" maxlength="100" />
        <x-field :form="$form" name="tgl_lahir" type="date" label="Tanggal lahir" :value="$employee->tgl_lahir" max="{{ now()->format('Y-m-d') }}" help="Wajib untuk CV; juga dipakai sebagai password awal (DDMMYY)." />
        <x-select :form="$form" name="agama" label="Agama" :options="$o['agama']" :value="$employee->agama" />
        <x-select :form="$form" name="status_nikah" label="Status nikah" :options="$o['status_nikah']" :value="$employee->status_nikah" />
        <x-select :form="$form" name="gol_darah" label="Golongan darah" :options="$o['gol_darah']" :value="$employee->gol_darah" />
        <x-field :form="$form" name="tinggi_badan_cm" type="number" label="Tinggi badan (cm)" :value="$employee->tinggi_badan_cm" min="1" max="400" />
        <x-field :form="$form" name="berat_badan_kg" type="number" step="0.1" label="Berat badan (kg)" :value="$employee->berat_badan_kg" min="1" max="999" />
    </div>
    <datalist id="citizen-list"><option value="Indonesian"><option value="Indonesia"></datalist>
</fieldset>

<fieldset class="mt-8 space-y-4">
    <legend class="mb-2 text-base font-semibold text-slate-900">Kontak</legend>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-field :form="$form" name="no_hp" type="tel" label="No. HP" :value="$employee->no_hp" maxlength="30" inputmode="tel" help="Minimal salah satu dari No. HP atau email diperlukan untuk CV." />
        <x-field :form="$form" name="email" type="email" label="Email" :value="$employee->email" maxlength="254" />
        <x-field :form="$form" name="alamat" type="textarea" label="Alamat" :value="$employee->alamat" class="sm:col-span-2 lg:col-span-3" help="Tidak dicetak pada CV." />
    </div>
</fieldset>

<fieldset class="mt-8 space-y-4">
    <legend class="mb-1 text-base font-semibold text-slate-900">Administrasi <span class="ml-1 text-xs font-normal text-slate-500">(rahasia — hanya HR, tidak dicetak pada CV)</span></legend>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-field :form="$form" name="no_ktp" label="No. KTP" :value="$employee->no_ktp" maxlength="32" inputmode="numeric" autocomplete="off" />
        <x-field :form="$form" name="kk" label="No. KK" :value="$employee->kk" maxlength="32" inputmode="numeric" autocomplete="off" />
        <x-field :form="$form" name="npwp" label="NPWP" :value="$employee->npwp" maxlength="32" autocomplete="off" />
        <x-field :form="$form" name="no_bpjs_tk" label="No. BPJS Ketenagakerjaan" :value="$employee->no_bpjs_tk" maxlength="40" autocomplete="off" />
        <x-field :form="$form" name="no_bpjs_kes" label="No. BPJS Kesehatan" :value="$employee->no_bpjs_kes" maxlength="40" autocomplete="off" />
        <x-select :form="$form" name="status_ptkp" label="Status PTKP" :options="$o['status_ptkp']" :value="$employee->status_ptkp" />
        <x-field :form="$form" name="nama_ibu" label="Nama ibu kandung" :value="$employee->nama_ibu" maxlength="150" autocomplete="off" />
    </div>
</fieldset>
