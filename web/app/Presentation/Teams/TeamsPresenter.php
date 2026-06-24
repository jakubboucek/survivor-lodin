<?php declare(strict_types=1);

namespace App\Presentation\Teams;

use App\Model\GameRepository;
use App\Model\TeamCode;
use App\Model\TeamRepository;
use Nette;


final class TeamsPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly TeamRepository $teams,
        private readonly GameRepository $games,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $now = new \DateTimeImmutable();

        // Teams keyed by internal code, with display name, crest and members.
        $teams = [];
        foreach ($this->teams->findAllOrdered() as $team) {
            $members = [];
            foreach ($this->teams->findMembers($team->code) as $member) {
                $members[] = ['name' => $member->name, 'photo' => $member->photo];
            }

            $teams[$team->code] = [
                'name' => $team->name,
                'crest' => TeamCode::from($team->code)->crest(),
                'members' => $members,
            ];
        }

        // Scored games, oldest first. Embargoed games (future published_at) are
        // shown without scores – the template prints the reveal time instead –
        // and are excluded from the running totals until released.
        $games = [];
        $totals = array_fill_keys(array_keys($teams), 0);
        foreach ($this->games->findScoredOrdered() as $game) {
            $revealed = $game->published_at === null || $game->published_at <= $now;

            $points = [];
            foreach (TeamCode::cases() as $code) {
                $points[$code->value] = $game->{$code->pointsColumn()};
            }

            $games[] = [
                'name' => $game->name,
                'revealed' => $revealed,
                // Reveal time is always shown as time only (no date) – an early-morning
                // next-day release reads as just the time. Far-future dates are not a concern.
                'revealTime' => $revealed ? null : $game->published_at->format('G:i'),
                'points' => $points,
            ];

            if ($revealed) {
                foreach ($points as $code => $value) {
                    $totals[$code] += $value;
                }
            }
        }

        $this->template->teams = $teams;
        $this->template->games = $games;
        $this->template->totals = $totals;
    }
}
