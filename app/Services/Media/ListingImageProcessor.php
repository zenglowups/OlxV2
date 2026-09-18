<?php

namespace App\Services\Media;

use App\Models\ListingPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListingImageProcessor
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    public function inspect(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            $this->invalid();
        }
        [$image, $metadata] = $this->decode($file->getRealPath());
        unset($image);

        return $metadata;
    }

    public function process(ListingPhoto $photo): array
    {
        $temporary = tmpfile();
        if ($temporary === false) {
            throw new \RuntimeException('Cannot create image processing temporary file.');
        }
        $input = null;
        try {
            $input = Storage::disk('s3')->readStream($photo->sourcePath());
            if (! is_resource($input)) {
                throw new \RuntimeException('Cannot read source image.');
            }
            stream_copy_to_stream($input, $temporary, self::MAX_BYTES + 1);
            $path = stream_get_meta_data($temporary)['uri'];
            [$source, $metadata] = $this->decode($path);
            if (! hash_equals($photo->source_hash, $metadata['source_hash'])) {
                $this->invalid();
            }
            $variants = [];
            foreach (ListingPhoto::VARIANTS as $variant => $bound) {
                $scale = min(1, $bound / $metadata['width'], $bound / $metadata['height']);
                $width = max(1, (int) round($metadata['width'] * $scale));
                $height = max(1, (int) round($metadata['height'] * $scale));
                $target = imagecreatetruecolor($width, $height);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $metadata['width'], $metadata['height']);
                ob_start();
                try {
                    if (! imagewebp($target, null, 82)) {
                        throw new \RuntimeException('Image encoding failed.');
                    }
                    $encoded = ob_get_contents();
                } finally {
                    ob_end_clean();
                    unset($target);
                }
                if (! $encoded || ! @getimagesizefromstring($encoded)) {
                    throw new \RuntimeException('Empty image output.');
                }
                if (! Storage::disk('s3')->put($photo->variantPath($variant), $encoded, ['visibility' => 'private', 'ContentType' => 'image/webp'])) {
                    throw new \RuntimeException('Image storage failed.');
                }
                $variants[$variant] = ['width' => $width, 'height' => $height, 'bytes' => strlen($encoded)];
                unset($encoded);
            }
            unset($source);

            return $variants;
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
            fclose($temporary);
        }
    }

    private function decode(string $path): array
    {
        $size = filesize($path);
        if (! $size || $size > self::MAX_BYTES) {
            $this->invalid();
        }
        $bytes = file_get_contents($path);
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || min($info[0], $info[1]) < 64 || max($info[0], $info[1]) > 6000 || $info[0] * $info[1] > 12000000) {
            $this->invalid();
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            $this->invalid();
        }
        $hash = hash('sha256', $bytes);
        unset($bytes);
        if ($info[2] === IMAGETYPE_JPEG) {
            $exif = @exif_read_data($path);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            // Mirror in source coordinates before rotating for EXIF transpose/transverse.
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($source, IMG_FLIP_HORIZONTAL);
            }
            $angle = match ($orientation) {
                3, 4 => 180, 5, 8 => 90, 6, 7 => -90, default => 0
            };
            if ($angle) {
                $source = imagerotate($source, $angle, 0);
            }
        }

        return [$source, ['width' => imagesx($source), 'height' => imagesy($source), 'source_hash' => $hash, 'source_bytes' => $size]];
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['photos' => __('photo.invalid')]);
    }
}
