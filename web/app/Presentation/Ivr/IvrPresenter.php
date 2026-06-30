<?php declare(strict_types=1);

namespace App\Presentation\Ivr;

use App\Model\IvrEndpointRepository;
use App\Model\IvrLogRepository;
use Nette;
use Nette\Application\Responses\TextResponse;
use Nette\Utils\Json;


/**
 * Public IVR endpoint for the ODORIK exchange remote control. The exchange calls
 * /ivr/<code> with the caller's DTMF input as the `dtmf` GET param; we compare it to the
 * endpoint's configured value and return one of two plain-text command bodies. Every hit is
 * logged (unknown/inactive codes too, with endpoint_id NULL). No auth – the endpoint is
 * intentionally public so the exchange can reach it. Lives on the main domain, no layout.
 */
final class IvrPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly IvrEndpointRepository $endpoints,
        private readonly IvrLogRepository $log,
    ) {
        parent::__construct();
    }


    public function actionDefault(string $code): void
    {
        $httpRequest = $this->getHttpRequest();
        $params = $httpRequest->getQuery();
        $dtmf = is_string($params['dtmf'] ?? null) ? $params['dtmf'] : '';
        $ip = $httpRequest->getRemoteAddress();

        $endpoint = $this->endpoints->findActive($code);

        if ($endpoint === null) {
            // Log the unknown/inactive hit for debugging the exchange integration, then 404.
            $this->log->insert([
                'endpoint_id' => null,
                'code' => $code,
                'dtmf' => $dtmf,
                'matched' => null,
                'params' => Json::encode($params),
                'response' => null,
                'ip' => $ip,
            ]);
            $this->error("IVR endpoint „{$code}“ nenalezen.");
        }

        $matched = trim($dtmf) === trim((string) $endpoint->expected_dtmf);
        $body = (string) ($matched ? $endpoint->response_correct : $endpoint->response_incorrect);

        $this->log->insert([
            'endpoint_id' => $endpoint->id,
            'code' => $code,
            'dtmf' => $dtmf,
            'matched' => $matched,
            'params' => Json::encode($params),
            'response' => $body,
            'ip' => $ip,
        ]);

        $this->getHttpResponse()->setContentType('text/plain', 'UTF-8');
        $this->sendResponse(new TextResponse($body));
    }
}
