# Amazon S3 sync

Synchronize files with an Amazon S3 bucket across multiple regions.

This module is **currently in development**.  You have been warned.

## Features

- Easy to set-up. All you need is your _AWS Access Key_, _Security Key_, and an existing _S3 bucket_.
- The ability to exclude files and directories (wildcard supported) during the S3 sync process.
- Support for CNAME based `Virtual Hosting` of publicly accessible files.

## Dependencies

- [Drupal 8](https://www.drupal.org)
- [s3cmd 1.6](https://github.com/s3tools/s3cmd)

## Installation

- Download the latest [release](https://github.com/nuxy/amazon_s3_sync/tags).
- Extract the contents of the _.zip_ into: `<drupal root>/modules/`

## Configuration

Once the module has been installed/enabled, you can navigate to `admin/config/media/amazon_s3_sync` **(Configuration > Media > Amazon S3 sync in the Admin panel)** to set-up the `s3cmd` sync options and AWS account settings.

[<img src="https://raw.githubusercontent.com/nuxy/amazon_s3_sync/master/screenshot.png" alt="Amazon S3 sync" />](https://nuxy.github.io/amazon_s3_sync)

Note: The following AWS settings can be configured in the config page or defined in the _settings.php_
```
$config['amazon_s3_sync.settings'] = array(
  's3_bucket_name' => '<S3 bucket name>',
  's3_access_key'  => '<AWS access key>',
  's3_secret_key'  => '<AWS secret key>',
);
```

## License and Warranty

This package is distributed in the hope that it will be useful, but without any warranty; without even the implied warranty of merchantability or fitness for a particular purpose.

_amazon_s3_sync_ is provided under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php)

## Author

[Marc S. Brooks](https://github.com/nuxy)
