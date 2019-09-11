# Imager Thumbor

A Thumbor transformer for Imager

## Configuration

Config options should be added to your `imager-thumbor.php` file in your config 
folder. To use Thumbor, set the `transformer` option to "thumbor" in your 
`imager.php` config file.

### domain [string]

The domain (including port) where Thumbor can be accessed.

### securityKey [string]

The security key defined in your Thumbor config file. See 
[Security](https://thumbor.readthedocs.io/en/latest/security.html).

### local [bool]

Will store the file locally (or using the remote storage service defined in 
Imager's config file), rather than using the Thumbor URL.

### webp [bool]

If true, a webp version of the transformed image will be generated and served
when appropriate, along side the original image type.
