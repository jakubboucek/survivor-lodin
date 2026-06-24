<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;


final readonly class TeamRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** Both teams in display order. */
    public function findAllOrdered(): Selection
    {
        return $this->explorer->table('team')->order('sort_order');
    }


    public function getByCode(string $code): ?ActiveRow
    {
        return $this->explorer->table('team')->get($code) ?: null;
    }


    /** Update an existing team (only the display name is editable). */
    public function update(string $code, array $data): void
    {
        $this->explorer->table('team')->wherePrimary($code)->update($data);
    }


    /** Members of a team, in display order. */
    public function findMembers(string $teamCode): Selection
    {
        return $this->explorer->table('team_member')
            ->where('team_code', $teamCode)
            ->order('sort_order');
    }


    public function getMember(int $id): ?ActiveRow
    {
        return $this->explorer->table('team_member')->get($id) ?: null;
    }


    public function insertMember(array $data): ActiveRow
    {
        return $this->explorer->table('team_member')->insert($data);
    }


    public function updateMember(int $id, array $data): void
    {
        $this->explorer->table('team_member')->wherePrimary($id)->update($data);
    }


    public function deleteMember(int $id): void
    {
        $this->explorer->table('team_member')->wherePrimary($id)->delete();
    }


    /** Next sort_order for a new member appended to the end of a team. */
    public function nextMemberOrder(string $teamCode): int
    {
        $max = $this->explorer->table('team_member')
            ->where('team_code', $teamCode)
            ->max('sort_order');

        return (int) $max + 1;
    }
}
