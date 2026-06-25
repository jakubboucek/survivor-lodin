<?php declare(strict_types=1);

namespace App\Presentation\Admin\Dashboard;

use App\Model\ShortlinkRepository;
use App\Presentation\Admin\BasePresenter;


final class DashboardPresenter extends BasePresenter
{
    public function __construct(
        private readonly ShortlinkRepository $shortlinks,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        $this->template->linkCount = $this->shortlinks->findAll()->count('*');
    }
}
