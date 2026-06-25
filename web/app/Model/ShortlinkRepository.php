<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Random;


/**
 * Short links (URL shortener). Each row maps a `code` (slug, may contain slashes)
 * to a target URL, optionally gated by a plaintext `password`. Read by the public
 * redirector (qr.<domain>) and managed in the admin (Admin\Links).
 */
final readonly class ShortlinkRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** All links, ordered by code – for the admin listing. */
    public function findAll(): Selection
    {
        return $this->explorer->table('shortlink')->order('code');
    }


    public function find(int $id): ?ActiveRow
    {
        return $this->explorer->table('shortlink')->get($id) ?: null;
    }


    /**
     * Returns an active link by its code, or null when the code is unknown or
     * disabled. The caller decides what to do with it (redirect / password gate).
     */
    public function findActive(string $code): ?ActiveRow
    {
        return $this->explorer->table('shortlink')
            ->where('code', $code)
            ->where('is_active', 1)
            ->fetch();
    }


    public function insert(array $data): void
    {
        $this->explorer->table('shortlink')->insert($data);
    }


    public function update(int $id, array $data): void
    {
        $this->explorer->table('shortlink')->where('id', $id)->update($data);
    }


    public function delete(int $id): void
    {
        $this->explorer->table('shortlink')->where('id', $id)->delete();
    }


    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $selection = $this->explorer->table('shortlink')->where('code', $code);
        if ($excludeId !== null) {
            $selection->where('id != ?', $excludeId);
        }

        return $selection->count('*') > 0;
    }


    /** A short random slug guaranteed to be unused. */
    public function generateUniqueCode(int $length = 6): string
    {
        do {
            $code = Random::generate($length, '0-9a-z');
        } while ($this->isCodeTaken($code));

        return $code;
    }
}
