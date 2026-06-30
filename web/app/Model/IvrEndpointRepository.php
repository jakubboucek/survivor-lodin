<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Random;


/**
 * IVR endpoints for the ODORIK exchange. Each row maps a `code` (used in the public URL
 * /ivr/<code>, may contain slashes) to an expected DTMF value and two plain-text command
 * bodies. Read by the public endpoint (App\Presentation\Ivr) and managed in the admin
 * (Admin\Ivr).
 */
final readonly class IvrEndpointRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** All endpoints, ordered by label – for the admin listing. */
    public function findAll(): Selection
    {
        return $this->explorer->table('ivr_endpoint')->order('label');
    }


    public function find(int $id): ?ActiveRow
    {
        return $this->explorer->table('ivr_endpoint')->get($id) ?: null;
    }


    /** Returns an active endpoint by its code, or null when unknown or disabled. */
    public function findActive(string $code): ?ActiveRow
    {
        return $this->explorer->table('ivr_endpoint')
            ->where('code', $code)
            ->where('is_active', 1)
            ->fetch();
    }


    /** Inserts a row and returns it (the caller needs the new id to redirect). */
    public function insert(array $data): ActiveRow
    {
        return $this->explorer->table('ivr_endpoint')->insert($data);
    }


    public function update(int $id, array $data): void
    {
        $this->explorer->table('ivr_endpoint')->where('id', $id)->update($data);
    }


    public function delete(int $id): void
    {
        $this->explorer->table('ivr_endpoint')->where('id', $id)->delete();
    }


    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $selection = $this->explorer->table('ivr_endpoint')->where('code', $code);
        if ($excludeId !== null) {
            $selection->where('id != ?', $excludeId);
        }

        return $selection->count('*') > 0;
    }


    /** A short random code guaranteed to be unused. */
    public function generateUniqueCode(int $length = 6): string
    {
        do {
            $code = Random::generate($length, '0-9a-z');
        } while ($this->isCodeTaken($code));

        return $code;
    }
}
