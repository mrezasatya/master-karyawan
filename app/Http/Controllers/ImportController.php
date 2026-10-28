<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Excel\EmployeeExporter;
use App\Services\Excel\EmployeeImporter;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ImportController extends Controller
{
    public function __construct(private EmployeeImporter $importer, private EmployeeExporter $exporter) {}

    public function index()
    {
        return view('imports.index', ['batches' => ImportBatch::with('uploader.employee')->latest('id')->paginate(15)]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls'],
        ], ['file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).', 'file.max' => 'Ukuran file maksimal 10 MB.']);

        $file = $request->file('file');
        $path = $file->storeAs('imports', Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');

        $batch = ImportBatch::create([
            'file_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'stored_path' => $path,
            'uploaded_by' => $request->user()->id,
            'status' => 'Preview',
        ]);

        try {
            $analysis = $this->importer->analyze(Storage::disk('local')->path($path));
            $batch->update(['total_rows' => $analysis['summary']['total']]);
        } catch (Throwable $e) {
            report($e);
            Storage::disk('local')->delete($path);
            $batch->update(['status' => 'Gagal', 'stored_path' => null, 'error_summary' => ['fatal' => $this->fatalMessage($e)]]);

            return redirect()->route('imports.index')->with('error', 'File tidak dapat diproses: '.$this->fatalMessage($e));
        }

        return redirect()->route('imports.show', $batch);
    }

    public function show(ImportBatch $batch)
    {
        if ($batch->status !== 'Preview') {
            return view('imports.result', ['batch' => $batch]);
        }
        abort_unless($batch->stored_path && Storage::disk('local')->exists($batch->stored_path), 410, 'File preview sudah tidak tersedia. Unggah ulang.');

        try {
            $analysis = $this->importer->analyze(Storage::disk('local')->path($batch->stored_path));
        } catch (Throwable $e) {
            return redirect()->route('imports.index')->with('error', 'File tidak dapat diproses: '.$this->fatalMessage($e));
        }

        return view('imports.preview', ['batch' => $batch, 'a' => $analysis]);
    }

    public function process(Request $request, ImportBatch $batch)
    {
        abort_unless($batch->status === 'Preview' && $batch->stored_path, 409, 'Batch ini sudah diproses.');
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Centang konfirmasi sebelum memproses import.']);

        $path = Storage::disk('local')->path($batch->stored_path);
        try {
            $this->importer->commit($path, $batch, $request->boolean('create_projects'), $request->boolean('create_accounts'));
        } catch (Throwable $e) {
            report($e);
            $batch->update(['status' => 'Gagal', 'error_summary' => ['fatal' => $this->fatalMessage($e)]]);

            return redirect()->route('imports.show', $batch)->with('error', 'Import dibatalkan seluruhnya karena error; tidak ada data yang disimpan. '.$this->fatalMessage($e));
        } finally {
            if ($batch->fresh()->status !== 'Preview') {
                Storage::disk('local')->delete($batch->stored_path);
                $batch->update(['stored_path' => null]);
            }
        }

        return redirect()->route('imports.show', $batch)->with('success', 'Import selesai diproses.');
    }

    public function cancel(ImportBatch $batch)
    {
        abort_unless($batch->status === 'Preview', 409);
        if ($batch->stored_path) {
            Storage::disk('local')->delete($batch->stored_path);
        }
        $batch->update(['status' => 'Dibatalkan', 'stored_path' => null]);

        return redirect()->route('imports.index')->with('success', 'Import dibatalkan. Tidak ada data yang disimpan.');
    }

    /** Laporan kesalahan/peringatan dalam bentuk Excel. Tidak memuat nilai data pribadi. */
    public function report(ImportBatch $batch)
    {
        $summary = $batch->error_summary ?? [];
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Laporan');
        $sheet->fromArray(['Sheet', 'Baris', 'NIK', 'Jenis', 'Pesan'], null, 'A1');
        $r = 2;
        foreach ([['rows', null], ['assignments', 'penugasan dilewati'], ['warnings', null]] as [$key, $type]) {
            foreach ($summary[$key] ?? [] as $item) {
                foreach ($item['messages'] as $message) {
                    $sheet->setCellValue([1, $r], $item['sheet'] ?? '');
                    $sheet->setCellValue([2, $r], $item['row']);
                    $sheet->setCellValueExplicit([3, $r], (string) $item['nik'], DataType::TYPE_STRING);
                    $sheet->setCellValue([4, $r], $type ?? $item['status']);
                    $sheet->setCellValue([5, $r], $message);
                    $r++;
                }
            }
        }
        foreach ($summary['mapping'] ?? [] as $field => $values) {
            foreach ($values as $value => $count) {
                $sheet->fromArray(['', '', '', 'pemetaan', "{$field}: \"{$value}\" tidak dikenali ({$count} baris)"], null, 'A'.$r++);
            }
        }
        if (! empty($summary['fatal'])) {
            $sheet->fromArray(['', '', '', 'fatal', $summary['fatal']], null, 'A'.$r++);
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(100);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        return $this->download($book, 'laporan-import-'.$batch->id.'.xlsx');
    }

    public function template()
    {
        return $this->download($this->exporter->build(null), 'template-master-karyawan.xlsx');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['q', 'status', 'project', 'jobtitle', 'kontrak']);
        $query = EmployeeController::filteredQuery($filters);
        Audit::log('employee', null, 'Export', null, null, ['rows' => (clone $query)->count(), 'filters' => array_filter($filters) ?: null]);

        return $this->download($this->exporter->build($query), 'master-karyawan-'.now()->format('Ymd-His').'.xlsx');
    }

    private function download(Spreadsheet $book, string $name)
    {
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function fatalMessage(Throwable $e): string
    {
        return $e instanceof \RuntimeException || $e instanceof Exception
            ? $e->getMessage()
            : 'Terjadi kesalahan saat membaca/menyimpan data. Hubungi administrator.';
    }
}
