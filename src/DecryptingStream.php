<?php

namespace StreamEncryption;

use Psr\Http\Message\StreamInterface;

class DecryptingStream implements StreamInterface
{
    private string $buffer;
    private int $position = 0;

    public function __construct(StreamInterface $source, string $mediaKey, string $mediaType)
    {
        // Step 1: Read the encrypted media stream (includes ciphertext + mac)
        $data = $source->getContents();

        if (strlen($data) < 10) {
            throw new \RuntimeException("Encrypted stream is too short.");
        }

        // Step 2: Expand mediaKey to 112 bytes with HKDF and split into iv, cipherKey, macKey
        $keys = WhatsAppMediaCrypto::expandMediaKey($mediaKey, $mediaType);

        // Step 3: Split into ciphertext and mac (last 10 bytes)
        $ciphertext = substr($data, 0, -10);
        $mac = substr($data, -10);

        // Step 4: Validate mac = first 10 bytes of HMAC(iv + ciphertext, macKey)
        if (!WhatsAppMediaCrypto::verifyMac($keys['iv'], $ciphertext, $keys['macKey'], $mac)) {
            throw new \RuntimeException("MAC verification failed.");
        }

        // Step 5: Decrypt ciphertext with AES-CBC using cipherKey and iv, then unpad
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $keys['cipherKey'], OPENSSL_RAW_DATA, $keys['iv']);
        $this->buffer = self::pkcs7Unpad($decrypted);
    }

    private static function pkcs7Unpad(string $data): string
    {
        $pad = ord($data[-1]);
        return substr($data, 0, -$pad);
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
}