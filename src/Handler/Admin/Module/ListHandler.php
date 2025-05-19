<?php

declare(strict_types=1);

namespace Pi\Core\Handler\Admin\Module;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\ModuleManager\ModuleManager;
use Pi\Core\Response\EscapingJsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ModuleManager */
    protected ModuleManager $moduleManager;

    protected array $disallowedNames = ['application', 'lmcCors'];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        ModuleManager            $moduleManager
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->moduleManager   = $moduleManager;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $loadedModules   = $this->moduleManager->getLoadedModules();
        $customModules   = $this->filterCustomModules($loadedModules);
        $baseModuleNames = array_map([$this, 'extractBaseName'], array_keys($customModules));
        $baseModuleNames = array_filter(array_values($baseModuleNames), [$this, 'isAllowedModule']);

        $result = [
            'result' => true,
            'data'   => array_values($baseModuleNames),
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, StatusCodeInterface::STATUS_OK);
    }

    /**
     * Filters out Laminas-related modules.
     *
     * @param array $modules
     *
     * @return array
     */
    private function filterCustomModules(array $modules): array
    {
        return array_filter($modules, function (string $className): bool {
            return !str_starts_with($className, 'Laminas\\');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Extracts the base module name and lowercases the first character.
     *
     * @param string $moduleName
     *
     * @return string
     */
    private function extractBaseName(string $moduleName): string
    {
        if (str_contains($moduleName, '\\')) {
            $parts = explode('\\', $moduleName);
            $base  = end($parts);
        } else {
            $base = $moduleName;
        }

        return lcfirst($base);
    }

    /**
     * Checks if a base module name is not disallowed.
     *
     * @param string $baseName
     *
     * @return bool
     */
    private function isAllowedModule(string $baseName): bool
    {
        return !in_array($baseName, $this->disallowedNames, true);
    }
}
