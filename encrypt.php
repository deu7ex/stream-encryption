<?php

require 'vendor/autoload.php';

use StreamEncryption\EncryptingStream;
use GuzzleHttp\Psr7\Utils;

try {
    $inputFile = $argv[1] ?? 'samples/VIDEO.original';
    $mediaType = $argv[2] ?? 'VIDEO';

    if (!$inputFile || !file_exists($inputFile)) {
        throw new Exception("❌ Specify the path to the file. Example: php encrypt.php samples/VIDEO.original VIDEO");
    }

    $in = Utils::streamFor(file_get_contents($inputFile));
    $mediaKey = file_get_contents('samples/VIDEO.key');
    $enc = new EncryptingStream($in, $mediaKey, strtoupper($mediaType));

    file_put_contents("$inputFile.encrypted", (string)$enc);
    file_put_contents("$inputFile.key", $enc->getMediaKey());

    if (in_array(strtoupper($mediaType), ['VIDEO', 'AUDIO'])) {
        file_put_contents("$inputFile.sidecar", $enc->getSidecar());
    }

    echo "✅ Encrypted successfully: $inputFile.encrypted\n";
} catch (Throwable $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}