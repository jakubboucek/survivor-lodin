<?php declare(strict_types=1);

namespace App\Model;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Nette\Utils\Strings;


/**
 * Stores uploaded files under web/data/files (outside the document root) – served
 * only through PHP (FilePresenter), never directly by the web server. The on-disk
 * name is a webalized "<Y-m-d> <title>" plus a random nonce, so the directory stays
 * tidy and names are unique; the human name lives in the DB (download_name) for the
 * Content-Disposition header.
 */
final readonly class FileStorage
{
    public function __construct(
        private string $directory,
    ) {
    }


    /**
     * Move the upload into storage under a unique, tidy name and return that name.
     * The name freezes the title at upload time (a later rename won't update it);
     * the nonce keeps it unique.
     */
    public function store(FileUpload $file, string $title): string
    {
        FileSystem::createDir($this->directory);

        $ext = Strings::lower(pathinfo($file->getUntrustedName(), PATHINFO_EXTENSION));
        $ext = preg_replace('~[^a-z0-9]~', '', $ext) ?: 'bin';

        $slug = Strings::webalize(date('Y-m-d') . ' ' . $title) ?: date('Y-m-d');
        $storageName = $slug . '-' . Random::generate(6) . '.' . $ext;

        $file->move($this->directory . '/' . $storageName);

        return $storageName;
    }


    /** Absolute path to a stored file (for FileResponse). */
    public function path(string $storageName): string
    {
        return $this->directory . '/' . $storageName;
    }


    /** Delete a stored file; no-op for null/empty or an already-missing file. */
    public function delete(?string $storageName): void
    {
        if ((string) $storageName === '') {
            return;
        }

        $path = $this->directory . '/' . $storageName;
        if (is_file($path)) {
            FileSystem::delete($path);
        }
    }
}
