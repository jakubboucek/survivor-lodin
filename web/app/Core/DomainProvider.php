<?php

declare(strict_types=1);

namespace App\Core;

readonly class DomainProvider
{
    public function __construct(
        private array $knownDomains,
        private \Nette\Http\Request $httpRequest
    ) {
    }


    /**
     * Checks if current domain matches one of known domains directly or as sup-domain. If matches, returns the main
     * known domain (without sub-domain part), otherwise returns whole domain as is (subdomains routing will not works).
     */
    public function getCurrentDomain(): string
    {
        $domain = $this->httpRequest->getUrl()->getDomain(0);

        $pattern = '/(?:^|\.)(?<domain>' . implode('|', array_map('preg_quote', $this->knownDomains)) . ')$/iD';
        $result = preg_match($pattern, $domain, $matches);

        return $result ? $matches['domain'] : $domain;
    }
}
