<?php declare(strict_types=1);

namespace App\Presentation\Admin\QrCodes;

use App\Model\QrCodeRepository;
use App\Presentation\Admin\BasePresenter;


final class QrCodesPresenter extends BasePresenter
{
    public function __construct(
        private readonly QrCodeRepository $qrCodes,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->codes = $this->qrCodes->findAll()->fetchAll();
    }
}
