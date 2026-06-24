<?php declare(strict_types=1);

namespace App\Presentation\Admin\Users;

use App\Model\AdminUserRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\Passwords;


/**
 * Management of admin users: list, create, edit (incl. setting a new password
 * without knowing the old one) and delete.
 */
final class UsersPresenter extends BasePresenter
{
    private ?ActiveRow $editedUser = null;


    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly Passwords $passwords,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->users = $this->users->findAll()->fetchAll();
    }


    public function actionEdit(?int $id = null): void
    {
        if ($id !== null) {
            $this->editedUser = $this->users->getById($id);
            if ($this->editedUser === null) {
                $this->error('Správce nenalezen.');
            }
        }
    }


    public function renderEdit(): void
    {
        $this->template->editedUser = $this->editedUser;
    }


    public function actionDelete(int $id): void
    {
        if ($id === $this->getUser()->getId()) {
            $this->flashMessage('Vlastní účet smazat nelze.', 'error');
            $this->redirect('default');
        }

        $this->users->delete($id);
        $this->flashMessage('Správce byl smazán.');
        $this->redirect('default');
    }


    protected function createComponentUserForm(): Form
    {
        $isEdit = $this->editedUser !== null;

        $form = new Form;
        $form->addEmail('email', 'E-mail')
            ->setRequired('Zadej e-mail.');
        $form->addText('nick', 'Přezdívka')
            ->setRequired('Zadej přezdívku.');
        $form->addCheckbox('is_active', 'Aktivní účet');

        $password = $form->addPassword('password', 'Heslo')
            ->setHtmlAttribute('autocomplete', 'new-password');

        if ($isEdit) {
            $password->setHtmlAttribute('placeholder', 'Nech prázdné pro zachování stávajícího');
            $form->setDefaults([
                'email' => $this->editedUser->email,
                'nick' => $this->editedUser->nick,
                'is_active' => (bool) $this->editedUser->is_active,
            ]);
        } else {
            $password->setRequired('Zadej heslo.');
            $form['is_active']->setDefaultValue(true);
        }

        $form->addSubmit('send', $isEdit ? 'Uložit' : 'Vytvořit');
        $form->onSuccess[] = $this->userFormSucceeded(...);

        return $form;
    }


    private function userFormSucceeded(Form $form, \stdClass $data): void
    {
        $values = [
            'email' => $data->email,
            'nick' => $data->nick,
            'is_active' => $data->is_active ? 1 : 0,
        ];
        if ($data->password !== '') {
            $values['password'] = $this->passwords->hash($data->password);
        }

        try {
            if ($this->editedUser !== null) {
                $this->users->update((int) $this->editedUser->id, $values);
                $this->flashMessage('Změny byly uloženy.');
            } else {
                $this->users->insert($values);
                $this->flashMessage('Správce byl vytvořen.');
            }
        } catch (UniqueConstraintViolationException) {
            $form->addError('Správce s tímto e-mailem už existuje.');
            return;
        }

        $this->redirect('default');
    }
}
