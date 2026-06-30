<?php declare(strict_types=1);

namespace App\Presentation\Admin\Ivr;

use App\Model\IvrEndpointRepository;
use App\Model\IvrLogRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;


/**
 * Admin for IVR endpoints ("IVR"). Each endpoint is a dynamic, publicly callable URL
 * (/ivr/<code>) that the ODORIK exchange hits during a call; the admin defines the expected
 * DTMF and the two plain-text command responses. Also exposes the call log.
 */
final class IvrPresenter extends BasePresenter
{
    private ?ActiveRow $editedEndpoint = null;


    public function __construct(
        private readonly IvrEndpointRepository $endpoints,
        private readonly IvrLogRepository $log,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->endpoints = $this->endpoints->findAll()->fetchAll();
    }


    public function actionEdit(?int $id = null): void
    {
        if ($id !== null) {
            $this->editedEndpoint = $this->endpoints->find($id);
            if ($this->editedEndpoint === null) {
                $this->error('IVR endpoint nenalezen.');
            }
        }
    }


    public function renderEdit(): void
    {
        $this->template->editedEndpoint = $this->editedEndpoint;

        if ($this->editedEndpoint !== null) {
            $this->template->publicUrl = $this->link('//:Ivr:default', ['code' => $this->editedEndpoint->code]);
        }
    }


    protected function createComponentEndpointForm(): Form
    {
        $form = new Form;

        $form->addText('label', 'Název')
            ->setRequired('Zadej název.')
            ->setHtmlAttribute('placeholder', 'Interní název endpointu');

        $form->addText('code', 'Adresa (kód)')
            ->setRequired(false)
            ->addRule($form::Pattern, 'Povolené znaky: písmena, číslice, - _ / .', '[\w\-/.]+')
            ->setHtmlAttribute('placeholder', 'nech prázdné = vygeneruje se náhodný');

        $form->addText('expected_dtmf', 'Očekávaný DTMF')
            ->setNullable()
            ->setHtmlAttribute('placeholder', 'např. 1234');

        $form->addTextArea('response_correct', 'Odpověď při shodě')
            ->setHtmlAttribute('placeholder', "answer\nplay:…\nhangup");

        $form->addTextArea('response_incorrect', 'Odpověď při neshodě');

        $form->addCheckbox('is_active', 'Aktivní')
            ->setDefaultValue(true);

        if ($this->editedEndpoint !== null) {
            $form->setDefaults([
                'label' => $this->editedEndpoint->label,
                'code' => $this->editedEndpoint->code,
                'expected_dtmf' => $this->editedEndpoint->expected_dtmf,
                'response_correct' => $this->editedEndpoint->response_correct,
                'response_incorrect' => $this->editedEndpoint->response_incorrect,
                'is_active' => (bool) $this->editedEndpoint->is_active,
            ]);
        }

        $form->addSubmit('send', $this->editedEndpoint !== null ? 'Uložit' : 'Vytvořit');
        $form->onSuccess[] = $this->endpointFormSucceeded(...);

        return $form;
    }


    private function endpointFormSucceeded(Form $form, \stdClass $data): void
    {
        $excludeId = $this->editedEndpoint !== null ? (int) $this->editedEndpoint->id : null;

        $code = trim((string) $data->code);
        if ($code === '') {
            $code = $this->endpoints->generateUniqueCode();
        } elseif ($this->endpoints->isCodeTaken($code, $excludeId)) {
            $form['code']->addError('Tuto adresu už používá jiný endpoint.');
            return;
        }

        $values = [
            'code' => $code,
            'label' => trim((string) $data->label),
            'expected_dtmf' => trim((string) $data->expected_dtmf),
            'response_correct' => (string) $data->response_correct,
            'response_incorrect' => (string) $data->response_incorrect,
            'is_active' => $data->is_active,
        ];

        if ($this->editedEndpoint === null) {
            $row = $this->endpoints->insert($values);
            $this->flashMessage('IVR endpoint byl vytvořen. Adresu níže vlož do nastavení ústředny.');
            $this->redirect('edit', (int) $row->id);
        }

        $this->endpoints->update($excludeId, $values);
        $this->flashMessage('Změny byly uloženy.');
        $this->redirect('default');
    }


    public function actionDelete(int $id): void
    {
        $endpoint = $this->endpoints->find($id);
        if ($endpoint === null) {
            $this->error('IVR endpoint nenalezen.');
        }

        $this->log->deleteByEndpoint($id);
        $this->endpoints->delete($id);
        $this->flashMessage('IVR endpoint byl smazán.');
        $this->redirect('default');
    }


    public function renderCalls(?int $id = null): void
    {
        if ($id !== null) {
            $endpoint = $this->endpoints->find($id);
            if ($endpoint === null) {
                $this->error('IVR endpoint nenalezen.');
            }
            $this->template->endpoint = $endpoint;
            $this->template->logs = $this->log->findByEndpoint($id)->limit(200)->fetchAll();
        } else {
            $this->template->endpoint = null;
            $this->template->logs = $this->log->findAll()->limit(200)->fetchAll();
        }
    }
}
