<x-select :form="$f" name="item_type" label="Jenis" :options="config('karyawan.options.training_type')" :value="$t?->item_type" required />
<x-field :form="$f" name="item_name" label="Nama training / sertifikasi / kompetensi" :value="$t?->item_name" required maxlength="200" class="sm:col-span-2" />
<x-field :form="$f" name="year" type="number" label="Tahun" :value="$t?->year" min="1950" max="{{ now()->year + 1 }}" inputmode="numeric" />
<x-field :form="$f" name="organizer" label="Penyelenggara" :value="$t?->organizer" maxlength="200" class="sm:col-span-2" />
<x-field :form="$f" name="certificate_number" label="Nomor sertifikat" :value="$t?->certificate_number" maxlength="100" />
<x-field :form="$f" name="issue_date" type="date" label="Tanggal terbit" :value="$t?->issue_date" />
<x-field :form="$f" name="expiry_date" type="date" label="Berlaku sampai" :value="$t?->expiry_date" />
