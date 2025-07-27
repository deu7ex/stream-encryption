<?php

namespace StreamEncryption;

use Psr\Http\Message\StreamInterface;

class EncryptingStream implements StreamInterface
{
    private StreamInterface $source;
    private string $mediaType;
    private string $mediaKey;
    private string $buffer;
    private int $position = 0;

    // Step 1: Accept mediaKey (32 bytes)
    // Step 2: Expand mediaKey to 112 bytes using HKDF with type-specific info
    // Step 3: Split into iv, cipherKey, macKey
    public function __construct(StreamInterface $source, ?string $mediaKey, string $mediaType)
    {
        $this->source = $source;
        $this->mediaType = $mediaType;

        try {
            // Step 1: Generate mediaKey if not provided
            $this->mediaKey = $mediaKey ?? random_bytes(32);

            // Step 2: Expand mediaKey to 112 bytes using HKDF with type-specific info
            // Step 3: Split into iv, cipherKey, macKey
            $keys = WhatsAppMediaCrypto::expandMediaKey($this->mediaKey, $mediaType);

            $plain = $source->getContents();

            // Step 4: Encrypt using AES-CBC with cipherKey and iv
            $padded = self::pkcs7Pad($plain, 16);
            $encrypted = openssl_encrypt($padded, 'aes-256-cbc', $keys['cipherKey'], OPENSSL_RAW_DATA, $keys['iv']);

            // Step 5: Sign iv + ciphertext using HMAC-SHA256 with macKey, truncate to 10 bytes
            $mac = WhatsAppMediaCrypto::generateMac($keys['iv'], $encrypted, $keys['macKey']);

            // Step 6: Append MAC to encrypted content
            $this->buffer = $encrypted . $mac;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Encryption failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function getMediaKey(): string
    {
        return $this->mediaKey;
    }

    public function getSidecar(): string
    {
        $keys = WhatsAppMediaCrypto::expandMediaKey($this->mediaKey, $this->mediaType);
        $encryptedOnly = substr($this->buffer, 0, -10);
        return WhatsAppMediaCrypto::generateSidecar($encryptedOnly, $keys['macKey']);
    }

    public function __toString(): string
    {
        return $this->buffer;
    }

    public function read($length): string
    {
        $chunk = substr($this->buffer, $this->position, $length);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function getContents(): string
    {
        return $this->buffer;
    }

    public function getSize(): ?int
    {
        return strlen($this->buffer);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->buffer);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function rewind(): void
    {
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
    }

    public function write($string): int
    {
        throw new \RuntimeException("Stream is not writable.");
    }

    public function detach()
    {
    }

    public function close(): void
    {
    }

    public function getMetadata($key = null)
    {
    }

    private static function pkcs7Pad(string $data, int $blockSize): string
    {
        $pad = $blockSize - (strlen($data) % $blockSize);
        return $data . str_repeat(chr($pad), $pad);
    }

}