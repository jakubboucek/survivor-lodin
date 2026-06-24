<?php declare(strict_types=1);

namespace App\Model;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ImageColor;
use Nette\Utils\Random;
use Nette\Utils\Strings;


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


    /**
     * Resize the upload to a centred 200×200 webp, store it, return the filename.
     * The filename is a webalized "<label> <Y-m-d>" plus a random nonce – purely
     * informative (it freezes the team/member state at upload time; a later rename
     * won't update it) and made unique by the nonce.
     */
    public function store(FileUpload $file, string $label): string
    {
        $image = $file->toImage();
        $this->applyExifOrientation($image, $file->getTemporaryFile());
        $image->resize(self::Size, self::Size, Image::Cover);

        FileSystem::createDir($this->directory);
        $slug = Strings::webalize($label . ' ' . date('Y-m-d'));
        $filename = $slug . '-' . Random::generate(6) . '.webp';
        $image->save($this->directory . '/' . $filename);

        return $filename;
    }


    /**
     * GD (and thus Nette Image) ignores the EXIF orientation tag that phone
     * cameras set, so a portrait photo would come out rotated. Bake the
     * orientation into the pixels before resizing. Only JPEG carries the tag.
     */
    private function applyExifOrientation(Image $image, string $path): void
    {
        if (!function_exists('exif_read_data') || @exif_imagetype($path) !== IMAGETYPE_JPEG) {
            return;
        }

        $orientation = @exif_read_data($path)['Orientation'] ?? 1;
        $bg = ImageColor::rgb(0, 0, 0, 0);

        // imagerotate() rotates counter-clockwise for a positive angle.
        switch ($orientation) {
            case 2: $image->flip(IMG_FLIP_HORIZONTAL); break;
            case 3: $image->rotate(180, $bg); break;
            case 4: $image->flip(IMG_FLIP_VERTICAL); break;
            case 5: $image->rotate(-90, $bg); $image->flip(IMG_FLIP_HORIZONTAL); break;
            case 6: $image->rotate(-90, $bg); break;
            case 7: $image->rotate(90, $bg); $image->flip(IMG_FLIP_HORIZONTAL); break;
            case 8: $image->rotate(90, $bg); break;
        }
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
