<?php declare(strict_types=1);

namespace App\Presentation\Admin\Dashboard;

use App\Model\QrCodeRepository;
use App\Presentation\Admin\BasePresenter;


final class DashboardPresenter extends BasePresenter
{
    public function __construct(
        private readonly QrCodeRepository $qrCodes,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->qrCount = $this->qrCodes->findAll()->count('*');
    }
}
