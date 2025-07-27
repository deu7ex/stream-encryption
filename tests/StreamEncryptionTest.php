<?php

use PHPUnit\Framework\TestCase;
use StreamEncryption\EncryptingStream;
use StreamEncryption\DecryptingStream;
use GuzzleHttp\Psr7\Utils;

class StreamEncryptionTest extends TestCase
{
    public function testVideoDecryption()
    {
        try {
            $original = file_get_contents(__DIR__ . '/../samples/VIDEO.original');
            $key = file_get_contents(__DIR__ . '/../samples/VIDEO.original.key');
            $encrypted = file_get_contents(__DIR__ . '/../samples/VIDEO.original.encrypted');
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error uploading source files: " . $e->getMessage(), 0, $e);
        }

        $stream = Utils::streamFor($encrypted);
        $dec = new DecryptingStream($stream, $key, 'VIDEO');

        $this->assertEquals($original, (string)$dec);
    }

    public function testVideoRoundTrip()
    {
        try {
            $original = file_get_contents(__DIR__ . '/../samples/VIDEO.original');
            $key = file_get_contents(__DIR__ . '/../samples/VIDEO.original.key');
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error uploading source files: " . $e->getMessage(), 0, $e);
        }

        $inputStream = Utils::streamFor($original);
        $enc = new EncryptingStream($inputStream, $key, 'VIDEO');

        $dec = new DecryptingStream(Utils::streamFor((string)$enc), $key, 'VIDEO');

        $this->assertEquals($original, (string)$dec);
    }

    public function testSidecarMatchesSample()
    {
        try {
            $key = file_get_contents(__DIR__ . '/../samples/VIDEO.original.key');
            $original = file_get_contents(__DIR__ . '/../samples/VIDEO.original');
            $expectedSidecar = file_get_contents(__DIR__ . '/../samples/VIDEO.original.sidecar');
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error uploading source files: " . $e->getMessage(), 0, $e);
        }

        $enc = new EncryptingStream(Utils::streamFor($original), $key, 'VIDEO');

        $this->assertEquals($expectedSidecar, $enc->getSidecar());
    }
}