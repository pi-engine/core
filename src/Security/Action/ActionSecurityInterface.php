<?php

declare(strict_types=1);

namespace Pi\Core\Security\Action;

interface ActionSecurityInterface
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function process(array $data): array;
}