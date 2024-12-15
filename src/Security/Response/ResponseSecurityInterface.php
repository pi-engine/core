<?php

declare(strict_types=1);

namespace Pi\Core\Security\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResponseSecurityInterface
{
    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}