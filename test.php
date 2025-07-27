<?php

require 'vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use StreamEncryption\DecryptingStream;

$stream = Utils::streamFor(file_get_contents('file.encrypted'));
$key = file_get_contents('file.key');
$dec = new DecryptingStream($stream, $key, 'IMAGE');

file_put_contents('file.decrypted', (string) $dec);
