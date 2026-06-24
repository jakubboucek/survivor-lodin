<?php declare(strict_types=1);

namespace App\Presentation\Teams;

use Nette;


final class TeamsPresenter extends Nette\Application\UI\Presenter
{
    public function renderDefault(): void
    {
        // Sample data – to be replaced with a real DB-backed repository.
        $this->template->teams = [
            ['name' => 'Žraloci', 'points' => 128],
            ['name' => 'Vlci', 'points' => 115],
            ['name' => 'Orli', 'points' => 97],
            ['name' => 'Lišky', 'points' => 84],
            ['name' => 'Medvědi', 'points' => 73],
        ];
    }
}
