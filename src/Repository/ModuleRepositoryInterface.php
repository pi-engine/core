<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

interface ModuleRepositoryInterface
{
    public function createTables(array $installStatements): array;

    public function updateTables(array $updateStatements): array;
}