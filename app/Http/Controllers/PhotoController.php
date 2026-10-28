<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function show(Employee $employee)
    {
        abort_unless($employee->foto_path && Storage::disk('local')->exists($employee->foto_path), 404);

        return response()->file(Storage::disk('local')->path($employee->foto_path), [
            'Cache-Control' => 'no-store, private',
            'Content-Type' => 'image/jpeg',
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $request->validateWithBag('foto', [
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('karyawan.photo_max_kb'), 'dimensions:min_width=150,min_height=150'],
        ], [
            'foto.mimes' => 'Foto harus berformat JPG atau PNG.',
            'foto.max' => 'Ukuran foto maksimal '.(config('karyawan.photo_max_kb') / 1024).' MB.',
            'foto.dimensions' => 'Resolusi foto terlalu kecil (minimal 150×150 piksel).',
            'foto.image' => 'File harus berupa gambar.',
        ]);

        $path = Photo::store($request->file('foto'));
        $old = $employee->foto_path;
        $employee->update(['foto_path' => $path]);
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return redirect()->route('employees.show', [$employee, 'tab' => 'foto'])->with('success', 'Foto profil disimpan.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->foto_path) {
            Storage::disk('local')->delete($employee->foto_path);
            $employee->update(['foto_path' => null]);
        }

        return redirect()->route('employees.show', [$employee, 'tab' => 'foto'])->with('success', 'Foto profil dihapus.');
    }
}
