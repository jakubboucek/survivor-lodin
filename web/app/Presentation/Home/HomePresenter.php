<?php declare(strict_types=1);

namespace App\Presentation\Home;

use App\Model\SettingRepository;
use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly SettingRepository $settings,
    ) {
        parent::__construct();
    }


    public function actionDefault(): void
    {
        // Once the game is active, the homepage sends visitors straight to the
        // results board; otherwise it shows the pre-game intro below.
        if ($this->settings->isGameActive()) {
            $this->redirect(':Teams:');
        }
    }


    // Pre-game landing uses the full-screen forest cover layout (@cover.latte).
    protected function beforeRender(): void
    {
        $this->setLayout('cover');
    }
}
