<?php declare(strict_types=1);

namespace App\Model;

use RuntimeException;


/**
 * Builds QR-code images via the goqr.me API (https://goqr.me/api/). The redirect
 * never depends on this – it is only used in the admin to preview and download a
 * link's QR code. `imageUrl()` is fine for an <img> preview; `fetch()` proxies the
 * bytes server-side so the admin can offer a real named download (the cross-origin
 * `download` attribute is ignored by browsers).
 */
final readonly class QrImageService
{
    private const string Endpoint = 'https://api.qrserver.com/v1/create-qr-code/';

    /** @var list<string> */
    public const array Formats = ['png', 'svg'];


    public function imageUrl(string $data, int $size = 300, string $format = 'png'): string
    {
        return self::Endpoint . '?' . http_build_query([
            'data' => $data,
            'size' => "{$size}x{$size}",
            'format' => $format,
            'margin' => 8,
            'ecc' => 'M',
        ]);
    }


    /**
     * Downloads the QR image from the API and returns the raw bytes.
     *
     * @throws RuntimeException when the API is unreachable or returns an error
     */
    public function fetch(string $data, int $size, string $format): string
    {
        $ch = curl_init($this->imageUrl($data, $size, $format));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $bytes = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if (!is_string($bytes) || $status !== 200) {
            throw new RuntimeException("QR API selhalo (HTTP {$status}): {$error}");
        }

        return $bytes;
    }
}
