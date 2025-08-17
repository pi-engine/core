<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

interface SlugRepositoryInterface
{
    public function updateAllSlugs(string $table, array $params): void;
}