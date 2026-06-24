<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
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


    /** Members of a team, in display order. */
    public function findMembers(string $teamCode): Selection
    {
        return $this->explorer->table('team_member')
            ->where('team_code', $teamCode)
            ->order('sort_order');
    }
}
