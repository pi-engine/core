<?php

declare(strict_types=1);

namespace Pi\Core\Service;

class MessageBrokerService implements ServiceInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }
}