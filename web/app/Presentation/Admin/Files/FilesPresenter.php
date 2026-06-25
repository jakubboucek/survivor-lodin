<?php declare(strict_types=1);

namespace App\Presentation\Admin\Files;

use App\Model\FileRepository;
use App\Model\FileStorage;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;


/**
 * Admin for downloadable files ("Soubory"). Upload a file under a slug (slashes
 * allowed); the public side serves it inline at /soubor/<slug>. Creation is a small
 * form (title, slug, file); metadata (download name, MIME, active) is edited
 * afterwards. Replacing the file always stores the new bytes; if its name/MIME
 * differ, a follow-up form asks whether to adopt the new metadata.
 */
final class FilesPresenter extends BasePresenter
{
    private ?ActiveRow $editedFile = null;
    private ?string $newName = null;
    private ?string $newMime = null;


    public function __construct(
        private readonly FileRepository $files,
        private readonly FileStorage $storage,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->files = $this->files->findAll()->fetchAll();
    }


    public function actionEdit(?int $id = null): void
    {
        if ($id !== null) {
            $this->editedFile = $this->files->find($id);
            if ($this->editedFile === null) {
                $this->error('Soubor nenalezen.');
            }
        }
    }


    public function renderEdit(): void
    {
        $this->template->editedFile = $this->editedFile;

        if ($this->editedFile !== null) {
            $this->template->publicUrl = $this->link('//:File:default', ['slug' => $this->editedFile->slug]);
        }
    }


    protected function createComponentFileForm(): Form
    {
        $form = new Form;

        $form->addText('title', 'Název')
            ->setRequired('Zadej název.')
            ->setHtmlAttribute('placeholder', 'Interní název souboru');

        $form->addText('slug', 'Adresa (slug)')
            ->setRequired(false)
            ->addRule($form::Pattern, 'Povolené znaky: písmena, číslice, - _ / .', '[\w\-/.]+')
            ->setHtmlAttribute('placeholder', 'nech prázdné = vygeneruje se náhodná');

        if ($this->editedFile === null) {
            // CREATE: the upload is required; metadata is derived from it.
            $form->addUpload('file', 'Soubor')
                ->setRequired('Vyber soubor k nahrání.');
        } else {
            // EDIT: no upload here (replacing is a separate form); edit metadata.
            $form->addText('download_name', 'Název souboru ke stažení')
                ->setRequired('Zadej název souboru.');
            $form->addText('mime_type', 'Typ (MIME)')
                ->setRequired('Zadej typ souboru.');
            $form->addCheckbox('is_active', 'Aktivní')
                ->setDefaultValue(true);

            $form->setDefaults([
                'title' => $this->editedFile->title,
                'slug' => $this->editedFile->slug,
                'download_name' => $this->editedFile->download_name,
                'mime_type' => $this->editedFile->mime_type,
                'is_active' => (bool) $this->editedFile->is_active,
            ]);
        }

        $form->addSubmit('send', $this->editedFile !== null ? 'Uložit' : 'Vytvořit a pokračovat');
        $form->onSuccess[] = $this->fileFormSucceeded(...);

        return $form;
    }


    private function fileFormSucceeded(Form $form, \stdClass $data): void
    {
        $excludeId = $this->editedFile !== null ? (int) $this->editedFile->id : null;

        $slug = trim((string) $data->slug);
        if ($slug === '') {
            $slug = $this->files->generateUniqueSlug();
        } elseif ($this->files->isSlugTaken($slug, $excludeId)) {
            $form['slug']->addError('Tuto adresu už používá jiný soubor.');
            return;
        }

        $title = trim((string) $data->title);

        if ($this->editedFile === null) {
            /** @var FileUpload $upload */
            $upload = $data->file;
            if (!$upload->isOk()) {
                $form['file']->addError('Nahrání souboru se nezdařilo.');
                return;
            }

            $storageName = $this->storage->store($upload, $title);
            $row = $this->files->insert([
                'slug' => $slug,
                'storage_name' => $storageName,
                'download_name' => $upload->getUntrustedName(),
                'mime_type' => $upload->getContentType() ?? 'application/octet-stream',
                'size' => $upload->getSize(),
                'title' => $title,
                'is_active' => true,
            ]);

            $this->flashMessage('Soubor byl nahrán. Teď můžeš upravit metadata.');
            $this->redirect('edit', (int) $row->id);
        }

        $this->files->update($excludeId, [
            'slug' => $slug,
            'download_name' => trim((string) $data->download_name),
            'mime_type' => trim((string) $data->mime_type),
            'title' => $title,
            'is_active' => $data->is_active,
        ]);
        $this->flashMessage('Změny byly uloženy.');
        $this->redirect('default');
    }


    public function actionReplace(int $id): void
    {
        $this->editedFile = $this->files->find($id);
        if ($this->editedFile === null) {
            $this->error('Soubor nenalezen.');
        }
    }


    public function renderReplace(): void
    {
        $this->template->editedFile = $this->editedFile;
    }


    protected function createComponentReplaceForm(): Form
    {
        $form = new Form;
        $form->addUpload('file', 'Nový soubor')
            ->setRequired('Vyber soubor k nahrání.');
        $form->addSubmit('send', 'Nahradit soubor');
        $form->onSuccess[] = $this->replaceFormSucceeded(...);

        return $form;
    }


    private function replaceFormSucceeded(Form $form, \stdClass $data): void
    {
        /** @var FileUpload $upload */
        $upload = $data->file;
        if (!$upload->isOk()) {
            $form['file']->addError('Nahrání souboru se nezdařilo.');
            return;
        }

        $id = (int) $this->editedFile->id;
        $oldStorageName = $this->editedFile->storage_name;

        // Always swap the bytes; metadata stays until the user confirms a change.
        $newStorageName = $this->storage->store($upload, (string) $this->editedFile->title);
        $newName = $upload->getUntrustedName();
        $newMime = $upload->getContentType() ?? 'application/octet-stream';

        $this->files->update($id, [
            'storage_name' => $newStorageName,
            'size' => $upload->getSize(),
        ]);
        $this->storage->delete($oldStorageName);

        $nameChanged = $newName !== $this->editedFile->download_name;
        $mimeChanged = $newMime !== $this->editedFile->mime_type;

        if (!$nameChanged && !$mimeChanged) {
            $this->flashMessage('Soubor byl nahrazen.');
            $this->redirect('edit', $id);
        }

        // Hand the freshly detected (differing) values to the confirmation form via
        // URL params – simple and good enough for this app.
        $this->flashMessage('Soubor byl nahrazen. Zkontroluj prosím metadata.');
        $this->redirect('confirmMeta', [
            'id' => $id,
            'name' => $nameChanged ? $newName : null,
            'mime' => $mimeChanged ? $newMime : null,
        ]);
    }


    public function actionConfirmMeta(int $id, ?string $name = null, ?string $mime = null): void
    {
        $this->editedFile = $this->files->find($id);
        if ($this->editedFile === null) {
            $this->error('Soubor nenalezen.');
        }

        // Nothing actually differs → nothing to confirm.
        if ($name === null && $mime === null) {
            $this->redirect('edit', $id);
        }

        $this->newName = $name;
        $this->newMime = $mime;
    }


    public function renderConfirmMeta(): void
    {
        $this->template->editedFile = $this->editedFile;
        $this->template->newName = $this->newName;
        $this->template->newMime = $this->newMime;
    }


    protected function createComponentConfirmForm(): Form
    {
        $form = new Form;

        if ($this->newName !== null) {
            $form->addHidden('new_name', $this->newName);
            $form->addRadioList('name_choice', 'Název souboru', [
                'keep' => 'Ponechat původní (' . $this->editedFile->download_name . ')',
                'new' => 'Změnit na nový (' . $this->newName . ')',
            ])->setDefaultValue('new');
        }

        if ($this->newMime !== null) {
            $form->addHidden('new_mime', $this->newMime);
            $form->addRadioList('mime_choice', 'Typ (MIME)', [
                'keep' => 'Ponechat původní (' . $this->editedFile->mime_type . ')',
                'new' => 'Změnit na nový (' . $this->newMime . ')',
            ])->setDefaultValue('new');
        }

        $form->addSubmit('send', 'Použít');
        $form->onSuccess[] = $this->confirmFormSucceeded(...);

        return $form;
    }


    private function confirmFormSucceeded(Form $form, \stdClass $data): void
    {
        $id = (int) $this->editedFile->id;
        $values = [];

        if (isset($data->new_name) && ($data->name_choice ?? null) === 'new') {
            $values['download_name'] = $data->new_name;
        }
        if (isset($data->new_mime) && ($data->mime_choice ?? null) === 'new') {
            $values['mime_type'] = $data->new_mime;
        }

        if ($values !== []) {
            $this->files->update($id, $values);
        }

        $this->flashMessage('Metadata byla aktualizována.');
        $this->redirect('edit', $id);
    }


    public function actionDelete(int $id): void
    {
        $file = $this->files->find($id);
        if ($file === null) {
            $this->error('Soubor nenalezen.');
        }

        $this->storage->delete($file->storage_name);
        $this->files->delete($id);
        $this->flashMessage('Soubor byl smazán.');
        $this->redirect('default');
    }
}
