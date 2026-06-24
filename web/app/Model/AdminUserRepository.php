<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;


/**
 * Admin users that may log into the administration. Passwords are stored as
 * hashes (see App\Core\Authenticator); this repository never hashes itself.
 */
final readonly class AdminUserRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** All admins, ordered by nick – for the admin listing. */
    public function findAll(): Selection
    {
        return $this->explorer->table('admin_user')->order('nick');
    }


    public function getById(int $id): ?ActiveRow
    {
        return $this->explorer->table('admin_user')->get($id);
    }


    public function findByEmail(string $email): ?ActiveRow
    {
        return $this->explorer->table('admin_user')->where('email', $email)->fetch() ?: null;
    }


    public function insert(array $data): ActiveRow
    {
        return $this->explorer->table('admin_user')->insert($data);
    }


    public function update(int $id, array $data): void
    {
        $this->explorer->table('admin_user')->wherePrimary($id)->update($data);
    }


    public function delete(int $id): void
    {
        $this->explorer->table('admin_user')->wherePrimary($id)->delete();
    }
}
