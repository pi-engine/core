<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Laminas\ModuleManager\ModuleManager;
use Pi\Core\Repository\ModuleRepositoryInterface;
use ReflectionClass;

class ModuleService implements ServiceInterface
{
    /** @var ModuleRepositoryInterface */
    protected ModuleRepositoryInterface $moduleRepository;

    /** @var ModuleManager */
    protected ModuleManager $moduleManager;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    /** @var array<string> */
    protected array $disallowedNames = ['application', 'lmccors'];

    public function __construct(
        ModuleRepositoryInterface $moduleRepository,
        ModuleManager             $moduleManager,
        UtilityService            $utilityService,
                                  $config
    ) {
        $this->moduleRepository = $moduleRepository;
        $this->moduleManager    = $moduleManager;
        $this->utilityService   = $utilityService;
        $this->config           = $config;
    }

    /**
     * Returns a list of allowed module names (normalized).
     *
     * @return string[]
     */
    public function getModuleList(): array
    {
        $loadedModules = $this->moduleManager->getLoadedModules();
        $loadedModules = $this->filterCustomModules($loadedModules);

        $baseModuleNames = array_map([$this, 'extractBaseName'], array_keys($loadedModules));
        $baseModuleNames = array_filter($baseModuleNames, [$this, 'isAllowedModule']);

        return array_values($baseModuleNames);
    }

    /**
     * Returns a map of module name => filesystem root path.
     *
     * @return array<string,string>
     */
    public function getModulePath(): array
    {
        $paths         = [];
        $loadedModules = $this->moduleManager->getLoadedModules();
        $loadedModules = $this->filterCustomModules($loadedModules);

        foreach ($loadedModules as $moduleName => $moduleObject) {
            $normalizedName = $this->extractBaseName($moduleName);

            if (!$this->isAllowedModule($normalizedName)) {
                continue;
            }

            $refClass   = new ReflectionClass($moduleObject);
            $moduleRoot = dirname(dirname($refClass->getFileName())); // <- move up one level

            $paths[$normalizedName] = $moduleRoot;
        }

        return $paths;
    }

    public function installOrUpdateDatabase(): array
    {
        $result = [];
        
        // 1. Collect all schema.sql and schema-update.sql paths from modules
        $schemasInstallPath = [];
        $schemasUpdatePath  = [];
        $modulesPath        = $this->getModulePath();
        foreach ($modulesPath as $moduleName => $modulePath) {
            $schemaInstallPath = sprintf('%s/data/schema.sql', $modulePath);
            $schemaUpdatePath  = sprintf('%s/data/schema-update.sql', $modulePath);
            if (file_exists($schemaInstallPath)) {
                $schemasInstallPath[$moduleName] = $schemaInstallPath;
            }
            if (file_exists($schemaUpdatePath)) {
                $schemasUpdatePath[$moduleName] = $schemaUpdatePath;
            }
        }

        // 2. Extract Table SQL statements
        $installStatements = [];
        foreach ($schemasInstallPath as $schemaInstallPath) {
            $fileContent = file_get_contents($schemaInstallPath);
            $parts       = array_filter(array_map('trim', explode(';', $fileContent)));
            foreach ($parts as $sql) {
                $installStatements[] = $sql;
            }
        }

        // 3. Add all new tables
        $result['table'] = $this->moduleRepository->createTables($installStatements);
        
        // 4. Extract Update SQL statements
        $updateStatements = [];
        foreach ($schemasUpdatePath as $schemaUpdatePath) {
            $fileContent = file_get_contents($schemaUpdatePath);
            $parts       = array_filter(array_map('trim', explode(';', $fileContent)));
            foreach ($parts as $sql) {
                $updateStatements[] = $sql;
            }
        }

        // 5. Update all new changes
        $result['update'] = $this->moduleRepository->updateTables($updateStatements);
        
        return $result;
    }

    /**
     * Filters out Laminas-related modules.
     *
     * @param array<string,object> $modules
     *
     * @return array<string,object>
     */
    private function filterCustomModules(array $modules): array
    {
        return array_filter(
            $modules,
            fn(string $className): bool => !str_starts_with($className, 'Laminas\\'),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Extracts the base module name and normalizes it (lowercase).
     */
    private function extractBaseName(string $moduleName): string
    {
        if (str_contains($moduleName, '\\')) {
            $parts = explode('\\', $moduleName);
            $base  = end($parts);
        } else {
            $base = $moduleName;
        }

        return strtolower($base);
    }

    private function isAllowedModule(string $baseName): bool
    {
        return !in_array($baseName, $this->disallowedNames, true);
    }
}
