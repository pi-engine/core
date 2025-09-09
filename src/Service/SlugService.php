<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Pi\Core\Repository\SlugRepositoryInterface;

class SlugService implements ServiceInterface
{
    /**
     * @var SlugRepositoryInterface
     */
    protected SlugRepositoryInterface $slugRepositoryInterface;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    public function __construct(
        SlugRepositoryInterface $slugRepositoryInterface,
        UtilityService          $utilityService,
                                $config
    ) {
        $this->slugRepositoryInterface = $slugRepositoryInterface;
        $this->utilityService          = $utilityService;
        $this->config                  = $config;
    }

    public function updateAllSlugs($params): void
    {
        if (isset($params['table']) && !empty($params['table'])) {
            // Set update params
            $updateParams = [];
            if (isset($params['limit']) && !empty($params['limit']) && is_numeric($params['limit'])) {
                $updateParams['limit'] = $params['limit'];
            }

            $tables = (array)$params['table'];
            foreach ($tables as $table) {
                $this->slugRepositoryInterface->updateAllSlugs($table, $updateParams);
            }
        }
    }
}