<?php

declare(strict_types=1);

namespace Pi\Core\Security;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use Throwable;

class Signature
{
    /* @var array */
    protected array $config;

    /** @var string */
    protected string $privateKey;

    /** @var string */
    protected string $publicKey;

    /**
     * @param array $config Configuration containing paths for private/public keys.
     * @throws RuntimeException If keys are missing or invalid.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadKeys();
    }

    /**
     * Load signature keys from environment (.env / secrets) or filesystem.
     *
     * @throws RuntimeException
     */
    private function loadKeys(): void
    {
        // Environment-injected PEM keys (for example, Vault, Docker secrets, etc.)
        $envKeys = [
            'private' => getenv('SIGNATURE_PRIVATE_KEY_PEM') ?: null,
            'public'  => getenv('SIGNATURE_PUBLIC_KEY_PEM') ?: null,
        ];

        // Normalize newline characters if needed
        foreach ($envKeys as $key => $value) {
            if ($value !== null) {
                $envKeys[$key] = str_replace(['\\n', "\r"], "\n", $value);
            }
        }

        // If environment keys exist and are valid, use them
        if (!empty($envKeys['private']) || !empty($envKeys['public'])) {
            if (
                empty($envKeys['private'])
                || empty($envKeys['public'])
                || !openssl_pkey_get_private($envKeys['private'])
                || !openssl_pkey_get_public($envKeys['public'])
            ) {
                throw new RuntimeException('InvalidEnvKeys: Environment-injected signature keys are missing or invalid.');
            }

            $this->privateKey = $envKeys['private'];
            $this->publicKey  = $envKeys['public'];
            return;
        }

        // Otherwise, load or create keys from file system
        $keys = $this->loadOrCreateKeyPair();
        $this->privateKey = $keys['private'];
        $this->publicKey  = $keys['public'];
    }

    /**
     * Load (or create if missing) a key pair from configured file paths.
     *
     *
     * @return array{private: string, public: string}
     * @throws RuntimeException
     */
    private function loadOrCreateKeyPair(): array
    {
        $privatePath = $this->config['private_key'] ?? null;
        $publicPath  = $this->config['public_key'] ?? null;

        if (empty($privatePath) || empty($publicPath)) {
            throw new RuntimeException("Missing signature key paths in config: private_key, public_key");
        }

        if (!file_exists($privatePath) || !file_exists($publicPath)) {
            $this->createKeys($privatePath, $publicPath);
        }

        return [
            'private' => file_get_contents($privatePath),
            'public'  => file_get_contents($publicPath),
        ];
    }

    /**
     * Sign data (Supports any table by passing an associative array)
     *
     * @param array $data Associative array of record data
     * @return string Base64 encoded signature
     * @throws RuntimeException
     */
    public function signData(array $data): string
    {
        unset($data['signature']);
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $privateKey = PublicKeyLoader::load($this->privateKey);
        if (!$privateKey instanceof RSA\PrivateKey) {
            throw new RuntimeException('Invalid private key type for signing.');
        }

        return base64_encode($privateKey->sign($dataString));
    }

    /**
     * Verify a digital signature against data.
     *
     * @param array       $data      The original data array
     * @param string|null $signature The signature stored in the database
     * @return bool True if signature is valid, false otherwise
     * @throws RuntimeException
     */
    public function verifySignature(array $data, ?string $signature): bool
    {
        unset($data['signature']);
        if ($signature === null) {
            return false;
        }

        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $publicKey = PublicKeyLoader::load($this->publicKey);
        if (!$publicKey instanceof RSA\PublicKey) {
            throw new RuntimeException('Invalid public key type for verification.');
        }

        return $publicKey->verify($dataString, base64_decode($signature));
    }

    /**
     * Sort data array according to a defined field order.
     *
     * @param array $data   Associative array of data
     * @param array $fields Ordered list of keys
     * @return array Sorted array
     */
    public function sortByFields(array $data, array $fields): array
    {
        $sorted = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sorted[$field] = $data[$field];
            }
        }

        return $sorted;
    }

    /**
     * Generates and saves a 4096-bit RSA key pair in PKCS8 format.
     *
     * @param string $privateKeyPath
     * @param string $publicKeyPath
     * @return void
     * @throws RuntimeException If key generation or file saving fails.
     */
    public function createKeys(string $privateKeyPath, string $publicKeyPath): void
    {
        try {
            $privateKey = RSA::createKey(4096);
            $publicKey  = $privateKey->getPublicKey();

            if (!file_put_contents($privateKeyPath, $privateKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save private key to {$privateKeyPath}");
            }

            if (!file_put_contents($publicKeyPath, $publicKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save public key to {$publicKeyPath}");
            }
        } catch (Throwable $e) {
            error_log("[ERROR] Signature Key Generation: " . $e->getMessage());
            throw new RuntimeException("Error generating RSA keys for signature. Please check logs.");
        }
    }
}
