<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class Photo
{
    /**
     * Menyimpan foto ke disk privat sebagai JPEG yang di-resize ulang.
     * Encode ulang membuang metadata (EXIF/lokasi) dan memastikan file benar-benar gambar.
     */
    public static function store(UploadedFile $file): string
    {
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $max = config('karyawan.photo_max_px');
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, $max / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $canvas = imagecreatetruecolor($nw, $nh);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();

        $path = 'photos/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }

    /** Data URI untuk disematkan pada PDF/preview. */
    public static function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
