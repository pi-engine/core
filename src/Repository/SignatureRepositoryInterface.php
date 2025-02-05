<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

interface SignatureRepositoryInterface
{
    public function updateSignature(string $table, int $id): void;

    public function updateAllSignatures(string $table): void;

    public function checkSignature(string $table, int $id): bool;

    public function checkAllSignatures(string $table): array;
}