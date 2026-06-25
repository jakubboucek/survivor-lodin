<?php declare(strict_types=1);

namespace App\Presentation\Admin\Links;

use App\Model\QrImageService;
use App\Model\ShortlinkRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Strings;


/**
 * Admin for short links ("Odkazy") – a URL shortener with QR codes as a feature on
 * top. Create with a random or custom slug (slashes allowed), optionally gate behind
 * a plaintext password, then preview/download the QR (PNG/SVG) for printing.
 */
final class LinksPresenter extends BasePresenter
{
    /** PNG/SVG download resolution (square). */
    private const int DownloadSize = 1000;

    private ?ActiveRow $editedLink = null;


    public function __construct(
        private readonly ShortlinkRepository $shortlinks,
        private readonly QrImageService $qrImages,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->links = $this->shortlinks->findAll()->fetchAll();
    }


    public function actionEdit(?int $id = null): void
    {
        if ($id !== null) {
            $this->editedLink = $this->shortlinks->find($id);
            if ($this->editedLink === null) {
                $this->error('Odkaz nenalezen.');
            }
        }
    }


    public function renderEdit(): void
    {
        $this->template->editedLink = $this->editedLink;

        if ($this->editedLink !== null) {
            $shortUrl = $this->shortUrl($this->editedLink->code);
            $this->template->shortUrl = $shortUrl;
            $this->template->qrPreviewUrl = $this->qrImages->imageUrl($shortUrl);
        }
    }


    public function actionDelete(int $id): void
    {
        if ($this->shortlinks->find($id) === null) {
            $this->error('Odkaz nenalezen.');
        }

        $this->shortlinks->delete($id);
        $this->flashMessage('Odkaz byl smazán.');
        $this->redirect('default');
    }


    /** Server-side proxy so the admin can download a properly named QR image file. */
    public function actionDownload(int $id, string $format = 'png'): void
    {
        $link = $this->shortlinks->find($id);
        if ($link === null) {
            $this->error('Odkaz nenalezen.');
        }
        if (!in_array($format, QrImageService::Formats, true)) {
            $this->error('Neznámý formát.');
        }

        $bytes = $this->qrImages->fetch($this->shortUrl($link->code), self::DownloadSize, $format);

        $filename = 'survival-lodin-qr-' . (Strings::webalize($link->code) ?: 'kod') . '.' . $format;
        $response = $this->getHttpResponse();
        $response->setContentType($format === 'svg' ? 'image/svg+xml' : 'image/png');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->sendResponse(new TextResponse($bytes));
    }


    protected function createComponentLinkForm(): Form
    {
        $form = new Form;
        $form->addText('label', 'Popis')
            ->setRequired(false);

        $form->addText('target_url', 'Cílová adresa (URL)')
            ->setRequired('Zadej cílovou adresu.')
            ->addRule($form::URL, 'Zadej platnou URL (včetně https://).')
            ->setHtmlAttribute('placeholder', 'https://…');

        $form->addText('code', 'Vlastní adresa (slug)')
            ->setRequired(false)
            ->addRule($form::Pattern, 'Povolené znaky: písmena, číslice, - _ / .', '[\w\-/.]+')
            ->setHtmlAttribute('placeholder', 'nech prázdné = vygeneruje se náhodná');

        $form->addText('password', 'Heslo')
            ->setRequired(false)
            ->setHtmlAttribute('placeholder', 'prázdné = bez hesla');

        $form->addCheckbox('is_active', 'Aktivní')
            ->setDefaultValue(true);

        if ($this->editedLink !== null) {
            $form->setDefaults([
                'target_url' => $this->editedLink->target_url,
                'code' => $this->editedLink->code,
                'password' => $this->editedLink->password,
                'label' => $this->editedLink->label,
                'is_active' => (bool) $this->editedLink->is_active,
            ]);
        }

        $form->addSubmit('send', $this->editedLink !== null ? 'Uložit' : 'Vytvořit');
        $form->onSuccess[] = $this->linkFormSucceeded(...);

        return $form;
    }


    private function linkFormSucceeded(Form $form, \stdClass $data): void
    {
        $excludeId = $this->editedLink !== null ? (int) $this->editedLink->id : null;

        $code = trim((string) $data->code);
        if ($code === '') {
            $code = $this->shortlinks->generateUniqueCode();
        } elseif ($this->shortlinks->isCodeTaken($code, $excludeId)) {
            $form['code']->addError('Tuto adresu už používá jiný odkaz.');
            return;
        }

        $password = trim((string) $data->password);

        $values = [
            'code' => $code,
            'target_url' => $data->target_url,
            'password' => $password !== '' ? $password : null,
            'label' => trim((string) $data->label) ?: null,
            'is_active' => $data->is_active,
        ];

        if ($this->editedLink !== null) {
            $this->shortlinks->update($excludeId, $values);
            $this->flashMessage('Změny byly uloženy.');
        } else {
            $this->shortlinks->insert($values);
            $this->flashMessage('Odkaz byl vytvořen.');
        }

        $this->redirect('default');
    }


    /** Absolute short-link URL on the qr.<domain> subdomain (what the QR encodes). */
    private function shortUrl(string $code): string
    {
        return $this->link('//:Redirect:default', ['code' => $code]);
    }
}
