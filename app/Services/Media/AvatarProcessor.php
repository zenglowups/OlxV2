<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AvatarProcessor
{
    public function store(UploadedFile $file, int $userId, bool $business): array
    {
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
            $this->invalid();
        }
        $bytes = file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] < 64 || $info[1] < 64 || $info[0] > 6000 || $info[1] > 6000 || $info[0] * $info[1] > 12000000) {
            $this->invalid();
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            $this->invalid();
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            $orientation = (int) ($exif['Orientation'] ?? 1);
            if (in_array($orientation, [3, 4], true)) {
                $source = imagerotate($source, 180, 0);
            }
            if (in_array($orientation, [5, 6], true)) {
                $source = imagerotate($source, -90, 0);
            }
            if (in_array($orientation, [7, 8], true)) {
                $source = imagerotate($source, 90, 0);
            }
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($source, IMG_FLIP_HORIZONTAL);
            }
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $target = imagecreatetruecolor(320, 320);
        $background = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $background);
        if ($business) {
            $scale = min(320 / $width, 320 / $height);
            $outWidth = max(1, (int) round($width * $scale));
            $outHeight = max(1, (int) round($height * $scale));
            imagecopyresampled($target, $source, (int) ((320 - $outWidth) / 2), (int) ((320 - $outHeight) / 2), 0, 0, $outWidth, $outHeight, $width, $height);
        } else {
            $side = min($width, $height);
            imagecopyresampled($target, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), 320, 320, $side, $side);
        }
        ob_start();
        try {
            if (! imagewebp($target, null, 82)) {
                throw new \RuntimeException('Image encoding failed.');
            }
            $encoded = ob_get_contents();
        } finally {
            ob_end_clean();
            unset($source, $target);
        }
        if (! $encoded || ! @getimagesizefromstring($encoded)) {
            throw new \RuntimeException('Empty image encoding result.');
        }
        $id = (string) Str::uuid();
        $path = 'profiles/'.$userId.'/'.$id.'.webp';
        Storage::disk('s3')->put($path, $encoded, ['visibility' => 'private', 'ContentType' => 'image/webp']);

        return ['id' => $id, 'path' => $path];
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['avatar' => __('profile.invalid_image')]);
    }
}
