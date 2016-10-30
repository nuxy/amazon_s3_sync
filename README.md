# Amazon S3 sync

Synchronize files with an Amazon S3 bucket across multiple regions.

This module is **currently in development**.  You have been warned.

## Dependencies

- [Drupal 8](https://www.drupal.org)
- [s3cmd](https://github.com/s3tools/s3cmd)

## Configuration

The following AWS settings can be configured in the config page or defined in the _settings.php_
```
$config['amazon_s3_sync.settings'] = array(
  's3_bucket_name' => '<S3 bucket name>',
  's3_access_key'  => '<AWS access key>',
  's3_secret_key'  => '<AWS secret key>',
);
```

## Author

[Marc S. Brooks](https://github.com/nuxy)
