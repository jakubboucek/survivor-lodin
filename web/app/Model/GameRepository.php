<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\Selection;


final readonly class GameRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /**
     * Scored games (both teams scored, guaranteed together by the DB CHECK),
     * oldest first – the public results board. Embargoed games (future
     * `published_at`) are included; the caller decides whether to reveal scores.
     */
    public function findScoredOrdered(): Selection
    {
        return $this->explorer->table('game')
            ->where('bear_points IS NOT NULL')
            ->order('played_at, id');
    }
}
