<x-select :form="$f" name="level" label="Jenjang" :options="config('karyawan.options.education_level')" :value="$ed?->level" required />
<x-field :form="$f" name="graduation_year" type="number" label="Tahun lulus" :value="$ed?->graduation_year" min="1950" max="{{ now()->year }}" inputmode="numeric" />
<x-field :form="$f" name="major" label="Jurusan" :value="$ed?->major" maxlength="150" />
<x-field :form="$f" name="institution" label="Institusi / sekolah" :value="$ed?->institution" required maxlength="200" />
