<?php

namespace Pi\Core\Security\Account;

interface AccountSecurityInterface
{
    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return int
     */
    public function getStatusCode(): int;
}