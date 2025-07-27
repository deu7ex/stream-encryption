<?php

namespace StreamEncryption;

class WhatsAppMediaCrypto
{
    public static function expandMediaKey(string $mediaKey, string $type): array
    {
        $infoStrings = [
            'IMAGE' => 'WhatsApp Image Keys',
            'VIDEO' => 'WhatsApp Video Keys',
            'AUDIO' => 'WhatsApp Audio Keys',
            'DOCUMENT' => 'WhatsApp Document Keys',
        ];

        $type = strtoupper($type);

        if (!isset($infoStrings[$type])) {
            throw new \Exception("Unknown media type: $type");
        }

        $info = $infoStrings[$type];

        $expandedKey = hash_hkdf('sha256', $mediaKey, 112, $info, '');

        return [
            'iv' => substr($expandedKey, 0, 16),
            'cipherKey' => substr($expandedKey, 16, 32),
            'macKey' => substr($expandedKey, 48, 32),
        ];
    }

    public static function generateMac(string $iv, string $ciphertext, string $macKey): string
    {
        return substr(hash_hmac('sha256', $iv . $ciphertext, $macKey, true), 0, 10);
    }

    public static function verifyMac(string $iv, string $ciphertext, string $macKey, string $mac): bool
    {
        $expected = self::generateMac($iv, $ciphertext, $macKey);
        return hash_equals($expected, $mac);
    }

    public static function generateSidecar(string $ciphertext, string $macKey): string
    {
        $blockSize = 64 * 1024;
        $result = '';

        $length = strlen($ciphertext);
        $offset = 0;

        while ($offset < $length) {
            $chunk = substr($ciphertext, $offset, $blockSize + 16);
            $mac = substr(hash_hmac('sha256', $chunk, $macKey, true), 0, 10);
            $result .= $mac;
            $offset += $blockSize;
        }

        return $result;
    }
}