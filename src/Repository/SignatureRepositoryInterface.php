<?php

declare(strict_types=1);

namespace Pi\Core\Repository;

interface SignatureRepositoryInterface
{
    public function updateSignature(string $table, int $id, array $fields): void;

    public function updateAllSignatures(string $table, array $fields): void;

    public function checkSignature(string $table, int $id, array $fields): bool;

    public function checkAllSignatures(string $table, array $fields): array;
}