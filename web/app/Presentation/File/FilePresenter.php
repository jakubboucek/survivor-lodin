<?php declare(strict_types=1);

namespace App\Presentation\File;

use App\Model\FileRepository;
use App\Model\FileStorage;
use Nette;
use Nette\Application\Responses\FileResponse;


/**
 * Public file server: resolves a slug to a stored file and streams it through PHP
 * from outside the document root. Lives on the main domain under /soubor/<slug>
 * (see RouterFactory). Files are shown inline by default (a PDF/image with the task);
 * ?download=1 forces a download instead.
 */
final class FilePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly FileRepository $files,
        private readonly FileStorage $storage,
    ) {
        parent::__construct();
    }


    public function actionDefault(string $slug, bool $download = false): void
    {
        $file = $this->files->findActive($slug);
        if ($file === null) {
            $this->error("Soubor „{$slug}“ nenalezen.");
        }

        $path = $this->storage->path($file->storage_name);
        if (!is_file($path)) {
            $this->error('Soubor nenalezen.');
        }

        // Never let the browser MIME-sniff a user-uploaded file into something
        // executable in our origin.
        $this->getHttpResponse()->setHeader('X-Content-Type-Options', 'nosniff');

        $this->sendResponse(new FileResponse(
            $path,
            $file->download_name,
            $file->mime_type,
            forceDownload: $download,
        ));
    }
}
