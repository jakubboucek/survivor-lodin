<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;


final readonly class QrCodeRepository
{
        public function __construct(
                private Explorer $explorer,
        ) {
        }


        /**
         * Returns the redirect target for an active QR code, or null when the code
         * is unknown or disabled.
         */
        public function findActiveTarget(string $code): ?string
        {
                $row = $this->explorer->table('qr_code')
                        ->where('code', $code)
                        ->where('is_active', 1)
                        ->fetch();

                return $row?->target_url;
        }
}
