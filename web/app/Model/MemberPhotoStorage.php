<?php declare(strict_types=1);

namespace App\Model;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\Random;


/**
 * Stores member avatars as 200×200 webp under a unique filename, so every upload
 * gets a fresh, cache-busting URL. The original upload is never kept; the previous
 * file is removed by the caller (on replace) or via delete() (on member removal).
 */
final readonly class MemberPhotoStorage
{
    private const Size = 200;


    public function __construct(
        private string $directory,
    ) {
    }


    /** Resize the upload to a centred 200×200 webp, store it, return the filename. */
    public function store(FileUpload $file): string
    {
        $image = $file->toImage();
        $image->resize(self::Size, self::Size, Image::Cover);

        FileSystem::createDir($this->directory);
        $filename = Random::generate(20) . '.webp';
        $image->save($this->directory . '/' . $filename);

        return $filename;
    }


    /** Delete a stored avatar; no-op for null/empty or an already-missing file. */
    public function delete(?string $filename): void
    {
        if ((string) $filename === '') {
            return;
        }

        $path = $this->directory . '/' . $filename;
        if (is_file($path)) {
            FileSystem::delete($path);
        }
    }
}
