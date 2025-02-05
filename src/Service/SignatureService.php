<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Pi\Core\Repository\SignatureRepository;
use Pi\Core\Security\Signature;

class SignatureService implements ServiceInterface
{
    /**
     * @var SignatureRepository
     */
    protected SignatureRepository $signatureRepository;

    /**
     * @var Signature
     */
    private Signature $signature;

    /* @var array */
    protected array $config;

    public function __construct(
        SignatureRepository $signatureRepository,
        Signature           $signature,
                            $config
    ) {
        $this->signatureRepository = $signatureRepository;
        $this->signature           = $signature;
        $this->config              = $config;
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
            $result = $this->signatureRepository->checkAllSignatures($params['table']);
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
            $this->signatureRepository->updateAllSignatures($params['table']);
        }
    }

    public function createKey(): void
    {
        $this->signature->createKeys();
    }
}