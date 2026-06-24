<?php declare(strict_types=1);

namespace App\Presentation\Teams;

use Nette;


final class TeamsPresenter extends Nette\Application\UI\Presenter
{
    public function renderDefault(): void
    {
        // Dummy data – to be replaced with a real DB-backed repository.
        // `key` is the internal crest name (medved/srsen); `name` is the real,
        // editable team name (currently a hardcoded placeholder).
        $teams = [
            'medved' => ['name' => 'Hrdinové', 'crest' => 'survival-lodin-crest-medved'],
            'srsen' => ['name' => 'Padouši', 'crest' => 'survival-lodin-crest-srsen'],
        ];

        // One row per game; `points` holds the score each team earned in it
        // (usually 0–1, exceptionally up to 5).
        $games = [
            ['name' => 'Lanová dráha', 'points' => ['medved' => 1, 'srsen' => 0]],
            ['name' => 'Šifrovačka', 'points' => ['medved' => 0, 'srsen' => 1]],
            ['name' => 'Noční bojovka', 'points' => ['medved' => 1, 'srsen' => 1]],
            ['name' => 'Lukostřelba', 'points' => ['medved' => 0, 'srsen' => 1]],
            ['name' => 'Stavba přístřešku', 'points' => ['medved' => 1, 'srsen' => 0]],
            ['name' => 'Velká hra v lese', 'points' => ['medved' => 5, 'srsen' => 3]],
            ['name' => 'Orientační běh', 'points' => ['medved' => 1, 'srsen' => 0]],
        ];

        $totals = array_fill_keys(array_keys($teams), 0);
        foreach ($games as $game) {
            foreach ($game['points'] as $teamKey => $points) {
                $totals[$teamKey] += $points;
            }
        }

        $this->template->teams = $teams;
        $this->template->games = $games;
        $this->template->totals = $totals;
    }
}
