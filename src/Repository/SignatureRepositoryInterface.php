<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

interface SignatureRepositoryInterface
{
    public function updateSignature(string $table, array $params): void;

    public function updateAllSignatures(string $table): void;

    public function checkSignature(string $table, array $params): bool;

    public function checkAllSignatures(string $table): array;
}