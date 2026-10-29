<div class="card grid gap-6 p-4 sm:grid-cols-[200px_1fr] sm:p-6">
    <div>
        @if ($employee->foto_path)
            <img src="{{ route('photos.show', $employee) }}?v={{ $employee->updated_at?->timestamp }}" alt="Foto {{ $employee->nama }}" class="aspect-[3/4] w-48 rounded-lg border border-slate-200 object-cover">
        @else
            <div class="flex aspect-[3/4] w-48 items-center justify-center rounded-lg border-2 border-dashed border-slate-300 text-center text-sm text-slate-400">Belum ada foto</div>
        @endif
    </div>
    <div>
        <h2 class="mb-1 text-base font-semibold">Foto profil CV</h2>
        <p class="mb-4 text-sm text-slate-500">JPG/PNG, maksimal {{ config('karyawan.photo_max_kb') / 1024 }} MB, minimal 150×150 piksel. Disarankan rasio 3:4 (pas foto). Foto disimpan privat dan metadata (EXIF) dihapus.</p>
        <form method="POST" action="{{ route('photos.store', $employee) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="_form" value="foto">
            <input type="file" name="foto" accept="image/jpeg,image/png" required class="block w-full text-sm file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:font-semibold file:text-brand-700">
            @if ($e = form_error('foto', 'foto'))<p class="text-sm text-red-600">{{ $e }}</p>@endif
            <button class="btn btn-primary">{{ $employee->foto_path ? 'Ganti Foto' : 'Unggah Foto' }}</button>
        </form>
        @if ($employee->foto_path)
            <form method="POST" action="{{ route('photos.destroy', $employee) }}" class="mt-3" onsubmit="return confirm('Hapus foto ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-ghost text-red-700">Hapus Foto</button>
            </form>
        @endif
    </div>
</div>
