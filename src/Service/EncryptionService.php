<?php

namespace App\Service;

class EncryptionService
{
    private const CIPHER = 'aes-256-cbc';
    private const IV_LENGTH = 16;
    private const HMAC_ALGO = 'sha256';

    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Encrypt JSON string + add HMAC integrity tag.
     *
     * @param string $json
     * @return string Base64 of iv || ciphertext || hmac
     */
    public function encrypt(string $json): string
    {
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);
        if ($iv === false) {
            throw new \RuntimeException("Unable to generate IV");
        }

        $ciphertext = openssl_encrypt(
            data: $json,
            cipher_algo: self::CIPHER,
            passphrase: $this->key,
            options: OPENSSL_RAW_DATA,
            iv: $iv
        );
        if ($ciphertext === false) {
            throw new \RuntimeException("Encryption failed");
        }

        $hmac = hash_hmac(self::HMAC_ALGO, $iv . $ciphertext, $this->key, true);
        $combined = $iv . $ciphertext . $hmac;

        return base64_encode($combined);
    }

    /**
     * Decrypt and verify integrity. Throws if corrupted / wrong key.
     *
     * @param string $base64Data
     * @return string The original JSON string
     * @throws \RuntimeException on failure / integrity check fail
     */
    public function decrypt(string $base64Data): string
    {
        $data = base64_decode($base64Data, true);
        if ($data === false) {
            throw new \RuntimeException("Base64 decode failed");
        }

        $iv = substr($data, 0, self::IV_LENGTH);
        $hmacLength = strlen(hash(self::HMAC_ALGO, '', true));
        $hmac = substr($data, -$hmacLength);
        $ciphertext = substr($data, self::IV_LENGTH, -$hmacLength);
        $calcHmac = hash_hmac(self::HMAC_ALGO, $iv . $ciphertext, $this->key, true);

        if (!hash_equals($hmac, $calcHmac)) {
            throw new \RuntimeException("Integrity check failed: invalid key or corrupted data");
        }

        $plaintext = openssl_decrypt(
            data: $ciphertext,
            cipher_algo: self::CIPHER,
            passphrase: $this->key,
            options: OPENSSL_RAW_DATA,
            iv: $iv
        );
        if ($plaintext === false) {
            throw new \RuntimeException("Decryption failed");
        }

        return $plaintext;
    }
}
