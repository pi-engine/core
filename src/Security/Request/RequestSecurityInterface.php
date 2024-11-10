<?php

namespace Pi\Core\Security\Request;

use Psr\Http\Message\ServerRequestInterface;

interface RequestSecurityInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return int
     */
    public function getStatusCode(): int;
}