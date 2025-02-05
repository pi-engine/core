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

    public function __construct($config)
    {
        $this->config = $config;

        // Validate config keys
        if (empty($this->config['private_key']) || empty($this->config['public_key'])) {
            throw new RuntimeException('Signature private key and public key paths must be provided in config.');
        }

        // If either key is missing, regenerate both
        if (!file_exists($this->config['private_key']) || !file_exists($this->config['public_key'])) {
            throw new RuntimeException('Signature private key and public key files not exist.');
        }
    }

    /**
     * Sign data (Supports any table by passing an associative array)
     *
     * @param array $data Associative array of record data
     *
     * @return string Base64 encoded signature
     */
    public function signData(array $data): string
    {
        // Sort data keys to ensure consistent hashing order
        ksort($data);

        // Convert array to a string (key=value pairs)
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Load and explicitly cast as RSA\PrivateKey
        $privateKey = PublicKeyLoader::load(file_get_contents($this->config['private_key']));

        if (!$privateKey instanceof RSA\PrivateKey) {
            throw new RuntimeException('Invalid private key type for check signature.');
        }

        return base64_encode($privateKey->sign($dataString));
    }

    /**
     * Verify a digital signature against data
     *
     * @param array       $data      The original data array
     * @param string|null $signature The signature stored in the database
     *
     * @return bool True if signature is valid, false otherwise
     */
    public function verifySignature(array $data, string|null $signature): bool
    {
        // Check row is not null
        if (is_null($signature)) {
            return false;
        }

        // Sort data keys to ensure consistent hashing order
        ksort($data);

        // Convert array to a string (key=value pairs)
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Load and explicitly cast as RSA\PublicKey
        $publicKey = PublicKeyLoader::load(file_get_contents($this->config['public_key']));

        if (!$publicKey instanceof RSA\PublicKey) {
            throw new RuntimeException('Invalid private key type for check signature.');
        }

        return $publicKey->verify($dataString, base64_decode($signature));
    }

    /**
     * Generates and saves a 4096-bit RSA key pair.
     *
     * This method automatically creates a private and public key in PKCS8 format
     * and saves them to the configured file paths.
     *
     * @return void
     * @throws RuntimeException If key generation or file saving fails.
     */
    public function createKeys(): void
    {
        try {
            // Generate a 4096-bit RSA private key
            $privateKey = RSA::createKey(4096);
            $publicKey  = $privateKey->getPublicKey();

            // Save PEM-formatted keys
            if (!file_put_contents($this->config['private_key'], $privateKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save signature private key to {$this->config['private_key']}");
            }

            if (!file_put_contents($this->config['public_key'], $publicKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save signature public key to {$this->config['public_key']}");
            }
        } catch (Throwable $e) {
            // Log the error
            error_log("[ERROR] RSA Key Generation: " . $e->getMessage());
            throw new RuntimeException("Error generating signature RSA keys. Please check logs.");
        }
    }
}