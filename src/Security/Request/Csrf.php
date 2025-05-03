<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Service\CsrfService;
use Psr\Http\Message\ServerRequestInterface;

class Csrf implements RequestSecurityInterface
{
    /** @var CsrfService */
    protected CsrfService $csrfService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'csrf';

    public function __construct(
        CsrfService $csrfService,
                    $config
    ) {
        $this->csrfService = $csrfService;
        $this->config      = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['xss']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get request and query body
        $requestParams = $request->getParsedBody();
        $csrfToken     = $requestParams['csrf_token'] ?? $request->getHeaderLine('X-Csrf-Token') ?? null;

        // Do check
        if (!empty($csrfToken)) {
            if (!$this->csrfService->validateCsrfToken($csrfToken, $securityStream['userData']['data'])) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Invalid CSRF token';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}