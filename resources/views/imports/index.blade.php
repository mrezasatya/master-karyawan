<x-layouts.app title="Import / Export">
    <x-page-header title="Import / Export Excel" />

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('imports.upload') }}" enctype="multipart/form-data" class="card space-y-3 p-4 sm:p-6">
            @csrf
            <h2 class="font-semibold">1. Unggah file Excel</h2>
            <p class="text-sm text-slate-600">Sistem akan memvalidasi dan menampilkan <strong>preview</strong> terlebih dahulu. Tidak ada data yang disimpan sebelum Anda mengonfirmasi.</p>
            <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:font-semibold file:text-brand-700">
            @error('file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="btn btn-primary">Unggah &amp; Periksa</button>
            <p class="text-xs text-slate-500">Mendukung file master lama (32 kolom, satu sheet) maupun template standar (sheet <em>Karyawan</em> + <em>PenugasanProyek</em>). Maks. 10 MB.</p>
        </form>
        <div class="card space-y-3 p-4 sm:p-6">
            <h2 class="font-semibold">Template &amp; export</h2>
            <p class="text-sm text-slate-600">Template standar memuat kolom lama ditambah kolom baru: <em>Kewarganegaraan, StatusKaryawan, TahunLulus, Bahasa</em>, serta sheet <em>PenugasanProyek</em> dan <em>Petunjuk</em>.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('imports.template') }}" class="btn btn-secondary">Unduh Template Kosong</a>
                <a href="{{ route('imports.export') }}" class="btn btn-secondary">Export Semua Karyawan</a>
            </div>
            <p class="text-xs text-slate-500">File export berisi data pribadi/administrasi. Simpan dan bagikan sesuai kebijakan pelindungan data perusahaan. Setiap export tercatat di audit log.</p>
        </div>
    </div>

    <h2 class="mb-3 font-semibold">Riwayat import</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>File</th><th>Pengunggah</th><th>Baris</th><th>Baru</th><th>Diperbarui</th><th>Gagal</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $b)
                    <tr>
                        <td class="whitespace-nowrap">{{ \App\Support\Format::dateTime($b->created_at) }}</td>
                        <td class="max-w-xs truncate">{{ $b->file_name }}</td>
                        <td>{{ $b->uploader?->displayName() }}</td>
                        <td>{{ $b->total_rows }}</td>
                        <td>{{ $b->created_count }}</td>
                        <td>{{ $b->updated_count }}</td>
                        <td>{{ $b->failed_count }}</td>
                        <td><x-badge :color="match ($b->status) { 'Selesai' => 'green', 'Preview' => 'blue', 'Gagal' => 'red', default => 'slate' }">{{ $b->status }}</x-badge></td>
                        <td class="text-right"><a href="{{ route('imports.show', $b) }}" class="link">{{ $b->status === 'Preview' ? 'Lanjutkan' : 'Detail' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-8 text-center text-slate-500">Belum ada import.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.app>
