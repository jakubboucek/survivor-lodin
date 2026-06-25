<?php declare(strict_types=1);

namespace App\Presentation\Unlock;

use App\Model\ShortlinkRepository;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;


/**
 * Public password gate for protected short links. Lives on the main domain (not the
 * light qr.<domain> subdomain) so it gets the game-styled parchment layout, similar
 * to the sign-in screen. The redirector bounces protected links here; on the correct
 * password we 302 to the real target.
 */
final class UnlockPresenter extends Nette\Application\UI\Presenter
{
    #[Persistent]
    public string $code = '';


    public function __construct(
        private readonly ShortlinkRepository $shortlinks,
    ) {
        parent::__construct();
    }


    public function actionDefault(string $code): void
    {
        $this->code = $code;

        $link = $this->shortlinks->findActive($code);
        if ($link === null) {
            $this->error('Neznámý odkaz.');
        }

        // No password set (e.g. link visited directly): nothing to unlock, just go.
        if ($link->password === null) {
            $this->redirectUrl($link->target_url, Nette\Http\IResponse::S302_Found);
        }
    }


    protected function createComponentUnlockForm(): Form
    {
        $form = new Form;
        $form->addPassword('password', 'Heslo')
            ->setRequired('Zadej heslo.');
        $form->addSubmit('send', 'Pokračovat');

        $form->onSuccess[] = $this->unlockFormSucceeded(...);

        return $form;
    }


    private function unlockFormSucceeded(Form $form, \stdClass $data): void
    {
        $link = $this->shortlinks->findActive($this->code);
        if ($link === null) {
            $this->error('Neznámý odkaz.');
        }

        // Constant-time compare even though the password is stored as plaintext.
        if ($link->password !== null && !hash_equals($link->password, $data->password)) {
            $form->addError('Nesprávné heslo.');
            return;
        }

        $this->redirectUrl($link->target_url, Nette\Http\IResponse::S302_Found);
    }
}
