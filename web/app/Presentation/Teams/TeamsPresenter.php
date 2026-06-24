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
        // `members`: one entry per team member; `photo` is an optional round avatar
        // (filename under /img/, e.g. 'members/jan.jpg'), null = name only. The crest
        // files below are just placeholders to demonstrate the avatar slot.
        $teams = [
            'medved' => [
                'name' => 'Hrdinové',
                'crest' => 'survival-lodin-crest-medved',
                'members' => [
                    ['name' => 'Anna Nováková', 'photo' => 'survival-lodin-crest-medved.webp'],
                    ['name' => 'Petr Svoboda', 'photo' => 'survival-lodin-crest-medved.webp'],
                    ['name' => 'Klára Dvořáková', 'photo' => null],
                    ['name' => 'Tomáš Procházka', 'photo' => null],
                    ['name' => 'Eliška Veselá', 'photo' => null],
                ],
            ],
            'srsen' => [
                'name' => 'Padouši',
                'crest' => 'survival-lodin-crest-srsen',
                'members' => [
                    ['name' => 'Marek Horák', 'photo' => null],
                    ['name' => 'Lucie Němcová', 'photo' => null],
                    ['name' => 'Jakub Pokorný', 'photo' => null],
                    ['name' => 'Tereza Marková', 'photo' => null],
                    ['name' => 'Ondřej Kučera', 'photo' => null],
                ],
            ],
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
