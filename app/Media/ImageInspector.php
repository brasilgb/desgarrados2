<?php

namespace App\Media;

use Illuminate\Validation\ValidationException;

/** Validates the real content of an upload; the client-provided name and MIME are never trusted. */
class ImageInspector
{
    /** @return array{mime: string, extension: string, width: int, height: int} */
    public function inspect(string $path): array
    {
        /** @var array<string, string> $allowed */
        $allowed = config('media.mimes');
        $detected = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! array_key_exists($detected, $allowed)) {
            $this->fail('Envie uma imagem JPEG, PNG ou WebP. SVG, HTML e outros formatos são recusados.');
        }
        $info = @getimagesize($path);
        if ($info === false || $info['mime'] !== $detected) {
            $this->fail('O conteúdo do arquivo não corresponde a uma imagem válida.');
        }
        [$width, $height] = [(int) $info[0], (int) $info[1]];
        $min = (int) config('media.min_dimension');
        $max = (int) config('media.max_dimension');
        if ($width < $min || $height < $min) {
            $this->fail("A imagem precisa ter pelo menos {$min} × {$min} pixels.");
        }
        if ($width > $max || $height > $max || $width * $height > (int) config('media.max_pixels')) {
            $this->fail("A imagem excede o limite de {$max} pixels por lado ou de 40 megapixels.");
        }

        return ['mime' => $detected, 'extension' => $allowed[$detected], 'width' => $width, 'height' => $height];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
