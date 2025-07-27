<?php

require 'vendor/autoload.php';

use StreamEncryption\DecryptingStream;
use GuzzleHttp\Psr7\Utils;

try {
    $encFile = $argv[1] ?? 'samples/VIDEO.original.encrypted';
    $keyFile = $argv[2] ?? 'samples/VIDEO.original.key';
    $mediaType = $argv[3] ?? 'VIDEO';

    if (!$encFile || !$keyFile || !file_exists($encFile) || !file_exists($keyFile)) {
        throw new Exception("❌ Specify the path to the encrypted file and the key. 
        Example: php decrypt.php samples/VIDEO.encrypted samples/VIDEO.key VIDEO");
    }

    $encrypted = Utils::streamFor(file_get_contents($encFile));
    $key = file_get_contents($keyFile);

    $dec = new DecryptingStream($encrypted, $key, strtoupper($mediaType));
    file_put_contents("$encFile.decrypted", (string)$dec);

    echo "✅ Decryption is complete: $encFile.decrypted\n";
} catch (Throwable $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}