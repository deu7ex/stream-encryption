# Stream Encryption for PSR-7 Streams

This Composer package implements encryption and decryption of PSR-7 streams using the same algorithm as WhatsApp for media files (image, audio, video, document).

## Features

- Encrypt and decrypt PSR-7 streams (AES-256-CBC + HMAC-SHA256)
- Supports media types: IMAGE, AUDIO, VIDEO, DOCUMENT
- Automatically generates 32-byte mediaKey if not provided
- Generates sidecar for streamable media (video/audio)
- CLI scripts for easy encryption/decryption
- Fully testable with PHPUnit

## Installation

```bash
composer require yourvendor/stream-encryption
```

```bash
composer install
```

## File Permissions for `samples/`

To ensure the CLI and tests run correctly, the `samples/` directory must be readable and writable.

Recommended settings:

```bash
chmod 755 samples       # Folder: owner can write, others can read
chmod 644 samples/*     # Files: readable and writable by owner
```

Make sure the PHP process or terminal user has write access to this folder if you're generating `.encrypted`, `.key`, or `.sidecar` files.

## Usage

### Encrypt a Stream (CLI)

```bash
php encrypt.php path/to/input.file MEDIA_TYPE
```

- `MEDIA_TYPE` = IMAGE, AUDIO, VIDEO, DOCUMENT (default: VIDEO)

Example:

```bash
php encrypt.php samples/VIDEO.original VIDEO
```

This will generate:

- `samples/VIDEO.original.encrypted`
- `samples/VIDEO.original.key`
- `samples/VIDEO.original.sidecar` (for VIDEO/AUDIO)

---

### Decrypt a Stream (CLI)

```bash
php decrypt.php path/to/file.encrypted path/to/file.key MEDIA_TYPE
```

Example:

```bash
php decrypt.php samples/VIDEO.original.encrypted samples/VIDEO.original.key VIDEO
```

This will generate:

- `samples/VIDEO.original.encrypted.decrypted`

---

## Programmatic Usage

### Encrypt

```php
require 'vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use StreamEncryption\EncryptingStream;

$stream = Utils::streamFor(file_get_contents('file.jpg'));
$mediaKey = file_get_contents('samples/VIDEO.key');
$enc = new EncryptingStream($stream, $mediaKey, 'IMAGE');

file_put_contents('file.encrypted', (string) $enc);
file_put_contents('file.key', $enc->getMediaKey());
```

### Decrypt

```php
require 'vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use StreamEncryption\DecryptingStream;

$stream = Utils::streamFor(file_get_contents('file.encrypted'));
$key = file_get_contents('file.key');
$dec = new DecryptingStream($stream, $key, 'IMAGE');

file_put_contents('file.decrypted', (string) $dec);
```

---

## Testing

Run PHPUnit tests:

```bash
vendor/bin/phpunit
```

Or using config:

```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

## License

MIT