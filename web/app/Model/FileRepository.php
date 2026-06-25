<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Random;


/**
 * Downloadable files. Each row maps a `slug` (may contain slashes) to a stored file
 * (`storage_name`), with editable `download_name`/`mime_type` metadata. Read by the
 * public file server (/soubor/<slug>) and managed in the admin (Admin\Files).
 */
final readonly class FileRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** All files, ordered by slug – for the admin listing. */
    public function findAll(): Selection
    {
        return $this->explorer->table('file')->order('slug');
    }


    public function find(int $id): ?ActiveRow
    {
        return $this->explorer->table('file')->get($id) ?: null;
    }


    /** Returns an active file by its slug, or null when unknown or disabled. */
    public function findActive(string $slug): ?ActiveRow
    {
        return $this->explorer->table('file')
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->fetch();
    }


    /** Inserts a row and returns it (the caller needs the new id to redirect). */
    public function insert(array $data): ActiveRow
    {
        return $this->explorer->table('file')->insert($data);
    }


    public function update(int $id, array $data): void
    {
        $this->explorer->table('file')->where('id', $id)->update($data);
    }


    public function delete(int $id): void
    {
        $this->explorer->table('file')->where('id', $id)->delete();
    }


    public function isSlugTaken(string $slug, ?int $excludeId = null): bool
    {
        $selection = $this->explorer->table('file')->where('slug', $slug);
        if ($excludeId !== null) {
            $selection->where('id != ?', $excludeId);
        }

        return $selection->count('*') > 0;
    }


    /** A short random slug guaranteed to be unused. */
    public function generateUniqueSlug(int $length = 6): string
    {
        do {
            $slug = Random::generate($length, '0-9a-z');
        } while ($this->isSlugTaken($slug));

        return $slug;
    }
}
