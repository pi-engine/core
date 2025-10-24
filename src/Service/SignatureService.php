<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Pi\Core\Repository\SignatureRepositoryInterface;
use Pi\Core\Security\Signature;

class SignatureService implements ServiceInterface
{
    /**
     * @var SignatureRepositoryInterface
     */
    protected SignatureRepositoryInterface $signatureRepositoryInterface;

    /**
     * @var Signature
     */
    private Signature $signature;

    /* @var array */
    protected array $config;

    public function __construct(
        SignatureRepositoryInterface $signatureRepositoryInterface,
        Signature                    $signature,
                                     $config
    ) {
        $this->signatureRepositoryInterface = $signatureRepositoryInterface;
        $this->signature                    = $signature;
        $this->config                       = $config;
    }

    public function checkSignature($table, $params): bool
    {
        return $this->signatureRepositoryInterface->checkSignature($table, $params);
    }

    public function checkAllSignatures($params): array
    {
        $result = [];
        if (
            isset($this->config['allowed_tables'])
            && !empty($this->config['allowed_tables'])
            && isset($params['table'])
            && in_array($params['table'], $this->config['allowed_tables'])
        ) {
            $result = $this->signatureRepositoryInterface->checkAllSignatures($params['table']);
        }

        return $result;
    }

    public function updateAllSignatures($params): void
    {
        if (
            isset($this->config['allowed_tables'])
            && !empty($this->config['allowed_tables'])
            && isset($params['table'])
            && in_array($params['table'], $this->config['allowed_tables'])
        ) {
            // Set update params
            $updateParams = [];
            if (isset($params['just_empty']) && !empty($params['just_empty'])) {
                $updateParams['just_empty'] = $params['just_empty'];
            }
            if (isset($params['limit']) && !empty($params['limit']) && is_numeric($params['limit'])) {
                $updateParams['limit'] = $params['limit'];
            }

            $this->signatureRepositoryInterface->updateAllSignatures($params['table'], $updateParams);
        }
    }

    public function createKey(): void
    {
        $this->signature->createKeys();
    }
}