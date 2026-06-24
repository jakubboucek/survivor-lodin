<?php declare(strict_types=1);

namespace App\Presentation\Admin\Options;

use App\Model\SettingRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;


/** Application-wide options. Currently just the game-active switch. */
final class OptionsPresenter extends BasePresenter
{
    public function __construct(
        private readonly SettingRepository $settings,
    ) {
        parent::__construct();
    }


    protected function createComponentOptionsForm(): Form
    {
        $form = new Form;
        $form->addCheckbox('game_active', 'Aktivovat hru')
            ->setDefaultValue($this->settings->isGameActive());

        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            $this->settings->setGameActive($data->game_active);
            $this->flashMessage('Možnosti byly uloženy.');
            $this->redirect('default');
        };

        return $form;
    }
}
