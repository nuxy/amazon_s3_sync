<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmd.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;

/**
 * Defines a Amazon_S3_Sync s3cmd object.
 */
class Amazon_S3_SyncS3cmd implements Amazon_S3_SyncS3cmdInterface {

  /**
   * @var bool
   */
  public $dry_run = FALSE;

  /**
   * @var bool
   */
  public $verbose = FALSE;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The settings instance.
   *
   * @return \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a Amazon_S3_SyncS3cmd object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sync($path, $source) {
    $config = $this->configFactory->get('amazon_s3_sync.config');

    $s3cmd_path = $config->get('s3cmd_path');

    if (file_exists($s3cmd_path)) {
      $regions = $config->get('aws_regions');
      foreach ($regions as $code => $region) {
        $enabled = in_array($code, $config->get('aws_region'));
        if ($enabled == FALSE) {
          continue;
        }

        $excludes = array_merge(

          // System files.
          array(
            '.htaccess',
            '*.php',
            '*.yml',
            'README.txt',
          ),
          $config->get('s3cmd_excludes', array())
        );

        $options = array();

        foreach ($excludes as $exclude) {
          if ($exclude) {
            $options[] = "--exclude '$exclude'";
          }
        }

        if ($this->dry_run) {
          $options[] = '--dry-run';
        }

        if ($this->verbose) {
          $options[] = '--verbose';
        }

        $access_key = $this->settings->get('s3_access_key') ? $this->settings->get('s3_access_key') : $config->get('s3_access_key');
        $secret_key = $this->settings->get('s3_secret_key') ? $this->settings->get('s3_secret_key') : $config->get('s3_secret_key');

        $options[] = '--access_key ' . $access_key;
        $options[] = '--secret_key ' . $secret_key;
        $options[] = '--region ' . $code;
        $options[] = '--delete-removed';
        $options[] = '--acl-public';

        try {
          $command = $s3cmd_path . ' sync ' . implode(' ', $options);

          shell_exec($command . ' ' . $path . $source . ' s3://' . $config->get('s3_bucket_name') . '/' . $source);

          return TRUE;
        }
        catch (Exception $e) {
          $this->logger->error($e->getMessage());

          return FALSE;
        }
      }
    }
  }
}
