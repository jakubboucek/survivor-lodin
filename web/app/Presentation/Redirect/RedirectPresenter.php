<?php declare(strict_types=1);

namespace App\Presentation\Redirect;

use App\Model\ShortlinkRepository;
use Nette;


/**
 * Mini app variant: resolves a scanned short link to its target and redirects.
 * Lives on the qr.<appDomain> subdomain (see RouterFactory). Password-protected
 * links are bounced to the styled unlock form on the main domain – this subdomain
 * stays intentionally light (no layout/assets).
 */
final class RedirectPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly ShortlinkRepository $shortlinks,
    ) {
        parent::__construct();
    }


    public function actionDefault(string $code): void
    {
        $link = $this->shortlinks->findActive($code);
        if ($link === null) {
            $this->error("Neznámý odkaz „{$code}“.");
        }

        if ($link->password !== null) {
            // Protected: hand off to the parchment-styled unlock form on the main
            // domain (absolute link – different domain than this subdomain).
            $this->redirect('//:Unlock:default', ['code' => $code]);
        }

        // 302 (Temporary) on purpose: the target is reconfigurable in the admin and
        // must not be cached permanently by browsers/scanners (301 would stick).
        $this->redirectUrl($link->target_url, Nette\Http\IResponse::S302_Found);
    }
}
