<?php declare(strict_types=1);

namespace App\Presentation\Admin\Teams;

use App\Model\MemberPhotoStorage;
use App\Model\TeamCode;
use App\Model\TeamRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;


/**
 * Management of the two teams and their members. Team identity (code/crest) is
 * fixed; only the display name is editable. A member's name and photo are edited
 * separately: the name form here, the photo upload in its own view (editPhoto).
 */
final class TeamsPresenter extends BasePresenter
{
    private ?ActiveRow $editedTeam = null;
    private ?ActiveRow $editedMember = null;
    private ?string $memberTeamCode = null;


    public function __construct(
        private readonly TeamRepository $teams,
        private readonly MemberPhotoStorage $photos,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $teams = [];
        foreach ($this->teams->findAllOrdered() as $team) {
            $teams[] = [
                'row' => $team,
                'dot' => TeamCode::from($team->code)->dot(),
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
        $this->template->teamDot = TeamCode::from($this->editedTeam->code)->dot();
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
        $this->template->teamDot = TeamCode::from($this->memberTeamCode)->dot();
    }


    public function actionDeleteMember(int $id): void
    {
        $member = $this->teams->getMember($id);
        if ($member === null) {
            $this->error('Člen nenalezen.');
        }

        $this->photos->delete($member->photo);
        $this->teams->deleteMember($id);
        $this->flashMessage('Člen byl smazán.');
        $this->redirect('default');
    }


    public function actionEditPhoto(int $id): void
    {
        $this->editedMember = $this->teams->getMember($id);
        if ($this->editedMember === null) {
            $this->error('Člen nenalezen.');
        }
    }


    public function renderEditPhoto(): void
    {
        $this->template->member = $this->editedMember;
        $this->template->teamDot = TeamCode::from($this->editedMember->team_code)->dot();
    }


    public function actionDeletePhoto(int $id): void
    {
        $member = $this->teams->getMember($id);
        if ($member === null) {
            $this->error('Člen nenalezen.');
        }

        $this->photos->delete($member->photo);
        $this->teams->updateMember($id, ['photo' => null]);
        $this->flashMessage('Fotka byla odebrána.');
        $this->redirect('default');
    }


    protected function createComponentPhotoForm(): Form
    {
        // Cap at the server's own upload limit (a larger rule would only warn).
        $maxBytes = $this->uploadLimitBytes();

        $form = new Form;
        $form->addUpload('photo', 'Fotka')
            ->setRequired('Vyber soubor s fotkou.')
            ->addRule($form::Image, 'Soubor musí být obrázek (JPEG, PNG, WebP, GIF).')
            ->addRule($form::MaxFileSize, 'Soubor je příliš velký.', $maxBytes);

        $form->addSubmit('send', 'Nahrát fotku');
        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            // Store the new file first, then drop the old one (unique name = fresh URL).
            $filename = $this->photos->store($data->photo);
            $this->photos->delete($this->editedMember->photo);
            $this->teams->updateMember((int) $this->editedMember->id, ['photo' => $filename]);

            $this->flashMessage('Fotka byla nahrána.');
            $this->redirect('default');
        };

        return $form;
    }


    /** The server's effective upload size limit (php.ini upload_max_filesize) in bytes. */
    private function uploadLimitBytes(): int
    {
        $value = ini_get('upload_max_filesize');
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
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
