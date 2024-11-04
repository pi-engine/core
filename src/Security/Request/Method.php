<?php

namespace Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Method implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'method';

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Get request method
        $method = $request->getMethod();
        if (!in_array($method, $this->config['method']['allow_method'] ?? ['POST'])) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [],
            ];
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
        return 'Access denied: Request method not allowed !';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}