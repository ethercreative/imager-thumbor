# Imager Thumbor

A Thumbor transformer for Imager

## Configuration

Config options should be added to your `imager.php` file in your config folder.
To use Thumbor, set the `transformer` option to "thumbor".

### thumborConfig [array]

The configuration object takes the following settings:

**domain (string):** The domain (including port) where Thumbor can be accessed.

**webp (bool):** If true, a webp version of the transformed image will be 
generated and served when appropriate, along side the original image type.

```
'thumborConfig' => [
    'domain' => 'http://localhost:8888/',
    'webp' => true,
],
```
