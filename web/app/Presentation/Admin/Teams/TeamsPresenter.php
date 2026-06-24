<?php declare(strict_types=1);

namespace App\Presentation\Admin\Teams;

use App\Model\TeamRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;


/**
 * Management of the two teams and their members. Team identity (code/crest) is
 * fixed; only the display name is editable. Member photos are NOT handled here –
 * they are uploaded in a separate view (see backlog).
 */
final class TeamsPresenter extends BasePresenter
{
    private ?ActiveRow $editedTeam = null;
    private ?ActiveRow $editedMember = null;
    private ?string $memberTeamCode = null;


    public function __construct(
        private readonly TeamRepository $teams,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $teams = [];
        foreach ($this->teams->findAllOrdered() as $team) {
            $teams[] = [
                'row' => $team,
                'members' => $this->teams->findMembers($team->code)->fetchAll(),
            ];
        }

        $this->template->teams = $teams;
    }


    public function actionEditTeam(string $code): void
    {
        $this->editedTeam = $this->teams->getByCode($code);
        if ($this->editedTeam === null) {
            $this->error('Tým nenalezen.');
        }
    }


    public function renderEditTeam(): void
    {
        $this->template->editedTeam = $this->editedTeam;
    }


    public function actionEditMember(?int $id = null, ?string $team = null): void
    {
        if ($id !== null) {
            $this->editedMember = $this->teams->getMember($id);
            if ($this->editedMember === null) {
                $this->error('Člen nenalezen.');
            }
            $this->memberTeamCode = $this->editedMember->team_code;
        } else {
            $this->memberTeamCode = (string) $team;
            if ($this->teams->getByCode($this->memberTeamCode) === null) {
                $this->error('Tým nenalezen.');
            }
        }
    }


    public function renderEditMember(): void
    {
        $this->template->editedMember = $this->editedMember;
        $this->template->team = $this->teams->getByCode($this->memberTeamCode);
    }


    public function actionDeleteMember(int $id): void
    {
        if ($this->teams->getMember($id) === null) {
            $this->error('Člen nenalezen.');
        }

        $this->teams->deleteMember($id);
        $this->flashMessage('Člen byl smazán.');
        $this->redirect('default');
    }


    protected function createComponentTeamForm(): Form
    {
        $form = new Form;
        $form->addText('name', 'Název týmu')
            ->setRequired('Zadej název týmu.');

        if ($this->editedTeam !== null) {
            $form->setDefaults(['name' => $this->editedTeam->name]);
        }

        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            $this->teams->update($this->editedTeam->code, ['name' => $data->name]);
            $this->flashMessage('Název týmu byl uložen.');
            $this->redirect('default');
        };

        return $form;
    }


    protected function createComponentMemberForm(): Form
    {
        $isEdit = $this->editedMember !== null;

        $form = new Form;
        $form->addText('name', 'Jméno')
            ->setRequired('Zadej jméno.');

        if ($isEdit) {
            $form->setDefaults(['name' => $this->editedMember->name]);
        }

        $form->addSubmit('send', $isEdit ? 'Uložit' : 'Přidat');
        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            if ($this->editedMember !== null) {
                $this->teams->updateMember((int) $this->editedMember->id, ['name' => $data->name]);
                $this->flashMessage('Změny byly uloženy.');
            } else {
                $this->teams->insertMember([
                    'team_code' => $this->memberTeamCode,
                    'name' => $data->name,
                    'sort_order' => $this->teams->nextMemberOrder($this->memberTeamCode),
                ]);
                $this->flashMessage('Člen byl přidán.');
            }

            $this->redirect('default');
        };

        return $form;
    }
}
