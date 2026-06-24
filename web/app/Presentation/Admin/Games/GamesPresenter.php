<?php declare(strict_types=1);

namespace App\Presentation\Admin\Games;

use App\Model\GameRepository;
use App\Model\TeamCode;
use App\Model\TeamRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;


/**
 * CRUD for games and their results. Scores are stored "wide" (both teams in one
 * row) and must be entered for both teams at once – or left empty for an unplayed
 * game. `published_at` embargoes the result until a given time (empty = publish now).
 */
final class GamesPresenter extends BasePresenter
{
    private ?ActiveRow $editedGame = null;


    public function __construct(
        private readonly GameRepository $games,
        private readonly TeamRepository $teams,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->games = $this->games->findAllOrdered()->fetchAll();
        $this->template->teamNames = $this->teamNames();
        $this->template->now = new \DateTimeImmutable();
    }


    public function actionEdit(?int $id = null): void
    {
        if ($id !== null) {
            $this->editedGame = $this->games->getById($id);
            if ($this->editedGame === null) {
                $this->error('Hra nenalezena.');
            }
        }
    }


    public function renderEdit(): void
    {
        $this->template->editedGame = $this->editedGame;
    }


    public function actionDelete(int $id): void
    {
        if ($this->games->getById($id) === null) {
            $this->error('Hra nenalezena.');
        }

        $this->games->delete($id);
        $this->flashMessage('Hra byla smazána.');
        $this->redirect('default');
    }


    protected function createComponentGameForm(): Form
    {
        $names = $this->teamNames();

        $form = new Form;
        $form->addText('name', 'Název hry')
            ->setRequired('Zadej název hry.');

        $form->addInteger('bear_points', 'Body ' . $names['bear'])
            ->setNullable()
            ->addRule($form::Min, 'Body nesmí být záporné.', 0);
        $form->addInteger('hornet_points', 'Body ' . $names['hornet'])
            ->setNullable()
            ->addRule($form::Min, 'Body nesmí být záporné.', 0);

        $form->addDateTime('played_at', 'Odehráno');
        $form->addDateTime('published_at', 'Zveřejnit výsledky');

        if ($this->editedGame !== null) {
            $form->setDefaults([
                'name' => $this->editedGame->name,
                'bear_points' => $this->editedGame->bear_points,
                'hornet_points' => $this->editedGame->hornet_points,
                'played_at' => $this->editedGame->played_at,
                'published_at' => $this->editedGame->published_at,
            ]);
        } else {
            // Prefill the play time with "now"; the user may clear it (then the game
            // sorts last, by id).
            $form['played_at']->setDefaultValue(new \DateTimeImmutable());
        }

        $form->addSubmit('send', $this->editedGame !== null ? 'Uložit' : 'Vytvořit');
        $form->onSuccess[] = $this->gameFormSucceeded(...);

        return $form;
    }


    private function gameFormSucceeded(Form $form, \stdClass $data): void
    {
        // Scores are entered for both teams at once: both filled, or both empty.
        if (($data->bear_points === null) !== ($data->hornet_points === null)) {
            $form->addError('Body zadej pro oba týmy, nebo nech obě prázdná (neodehráno).');
            return;
        }

        $values = [
            'name' => $data->name,
            'bear_points' => $data->bear_points,
            'hornet_points' => $data->hornet_points,
            'played_at' => $data->played_at,
            'published_at' => $data->published_at,
        ];

        if ($this->editedGame !== null) {
            $this->games->update((int) $this->editedGame->id, $values);
            $this->flashMessage('Změny byly uloženy.');
        } else {
            $this->games->insert($values);
            $this->flashMessage('Hra byla vytvořena.');
        }

        $this->redirect('default');
    }


    /**
     * Current display names prefixed with the team's coloured dot, keyed by code:
     * ['bear' => '🟢 …', 'hornet' => '🟡 …']. The dot distinguishes the teams even
     * after a rename.
     */
    private function teamNames(): array
    {
        $names = [];
        foreach ($this->teams->findAllOrdered() as $team) {
            $names[$team->code] = TeamCode::from($team->code)->dot() . ' ' . $team->name;
        }

        return $names;
    }
}
