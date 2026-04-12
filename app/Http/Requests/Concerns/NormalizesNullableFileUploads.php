<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * Les inputs file vides peuvent encore être présents et faire échouer "image"/"mimes".
 * On fusionne null / tableaux filtrés quand aucun fichier valide n'est envoyé.
 */
trait NormalizesNullableFileUploads
{
    protected function prepareForValidation(): void
    {
        foreach (['featured_image', 'hotel_logo'] as $key) {
            if (! $this->hasFile($key)) {
                $this->merge([$key => null]);
            }
        }

        if (! $this->hasFile('gallery_images')) {
            return;
        }

        $files = $this->file('gallery_images');
        if (! is_array($files)) {
            return;
        }

        $valid = array_values(array_filter(
            $files,
            fn ($f) => $f instanceof UploadedFile && $f->isValid() && $f->getPathname() !== ''
        ));

        $this->merge(['gallery_images' => $valid]);
    }
}
