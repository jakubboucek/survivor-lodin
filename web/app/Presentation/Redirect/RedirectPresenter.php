<?php declare(strict_types=1);

namespace App\Presentation\Redirect;

use App\Model\QrCodeRepository;
use Nette;


/**
 * Mini app variant: resolves a scanned QR code to its configured target and redirects.
 * Lives on the qr.<appDomain> subdomain (see RouterFactory).
 */
final class RedirectPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly QrCodeRepository $qrCodes,
    ) {
        parent::__construct();
    }


    public function actionDefault(string $code): void
    {
        $target = $this->qrCodes->findActiveTarget($code);
        if ($target === null) {
            $this->error("Neznámý QR kód „{$code}“.");
        }

        // 302 (Temporary) on purpose: the target is reconfigurable in the admin and
        // must not be cached permanently by browsers/scanners (301 would stick).
        $this->redirectUrl($target, Nette\Http\IResponse::S302_Found);
    }
}
