<?php

declare(strict_types=1);

namespace Pi\Core\Security;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use RuntimeException;

class SignatureBySha512
{
    private string $method;
    private ?string $privateKeyPath = null;
    private ?string $publicKeyPath = null;
    private ?string $hmacSecret = null;

    public function __construct(array $config)
    {
        $this->method = $config['method'] ?? 'rsa'; // Default: RSA

        if ($this->method === 'rsa') {
            $this->privateKeyPath = $config['private_key'] ?? throw new RuntimeException('Private key path not set.');
            $this->publicKeyPath = $config['public_key'] ?? throw new RuntimeException('Public key path not set.');

            // Generate keys if they do not exist
            if (!file_exists($this->privateKeyPath) || !file_exists($this->publicKeyPath)) {
                $this->createKeys();
            }
        } elseif ($this->method === 'hmac_sha512') {
            $this->hmacSecret = $config['hmac_secret'] ?? throw new RuntimeException('HMAC secret key not set.');
        } else {
            throw new RuntimeException('Invalid signing method. Use "rsa" or "hmac_sha512".');
        }
    }

    /**
     * Sign data using the configured method
     *
     * @param array $data Associative array of record data
     * @return string Base64-encoded signature
     */
    public function signData(array $data): string
    {
        ksort($data); // Ensure consistent hashing
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($this->method === 'rsa') {
            $privateKey = PublicKeyLoader::load(file_get_contents($this->privateKeyPath));

            if (!$privateKey instanceof RSA\PrivateKey) {
                throw new RuntimeException('Invalid private key type.');
            }

            return base64_encode($privateKey->withHash('sha512')->sign($dataString));
        }

        // HMAC-SHA512 Signature
        return base64_encode(hash_hmac('sha512', $dataString, $this->hmacSecret, true));
    }

    /**
     * Verify a signature against the provided data
     *
     * @param array $data The original data
     * @param string $signature The stored signature
     * @return bool True if valid, false otherwise
     */
    public function verifySignature(array $data, string $signature): bool
    {
        ksort($data);
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $decodedSignature = base64_decode($signature);

        if ($this->method === 'rsa') {
            $publicKey = PublicKeyLoader::load(file_get_contents($this->publicKeyPath));

            if (!$publicKey instanceof RSA\PublicKey) {
                throw new RuntimeException('Invalid public key type.');
            }

            return $publicKey->withHash('sha512')->verify($dataString, $decodedSignature);
        }

        // HMAC-SHA512 Verification
        return hash_equals(
            base64_encode(hash_hmac('sha512', $dataString, $this->hmacSecret, true)),
            $signature
        );
    }

    /**
     * Generate RSA key pair
     */
    private function createKeys(): void
    {
        $privateKey = RSA::createKey(4096);
        $publicKey = $privateKey->getPublicKey();

        file_put_contents($this->privateKeyPath, $privateKey);
        file_put_contents($this->publicKeyPath, $publicKey);
    }
}
