<?php declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
    // Pre-game landing uses the full-screen forest cover layout (@cover.latte).
    protected function beforeRender(): void
    {
        $this->setLayout('cover');
    }
}
