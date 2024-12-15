<?php

declare(strict_types=1);

namespace Pi\Core\Installer;

use Pi\Core\Service\InstallerService;

class Install implements InstallerInterface
{
    /* @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(InstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    public function database($sqlFile): void
    {
        // Set and check
        if (!file_exists($sqlFile)) {
            echo 'Error to find or read sql file';
            exit();
        }

        echo 'Module database install successfully !';
    }

    public function config($configFile): void
    {
        // Set and check
        if (!file_exists($configFile)) {
            echo 'Error to find or read config file';
            exit();
        }

        echo 'Module config install successfully !';
    }

    public function permission($permissionFile): void
    {
        // Set and check
        if (!file_exists($permissionFile)) {
            echo 'Error to find or read permission file';
            exit();
        }

        $permissionConfig = include $permissionFile;

        $this->installerService->installPermission($permissionConfig);

        echo 'Module permission install successfully !';
    }
}