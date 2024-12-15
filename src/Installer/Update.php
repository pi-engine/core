<?php

declare(strict_types=1);

namespace Pi\Core\Installer;

use Pi\Core\Service\InstallerService;

class Update implements InstallerInterface
{
    /* @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(InstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    public function database($sqlFile): void
    {
    }

    public function config($configFile): void
    {
    }

    public function permission($permissionFile): void
    {
    }
}