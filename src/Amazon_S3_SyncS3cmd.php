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
   * @var array
   */
  public static $excludes = array(
    '.htaccess',
    '*.php',
    '*.yml',
    'README.txt',
  );

  /**
   * @var object
   */
  private $config;

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
    $this->config = $config_factory->get('amazon_s3_sync.config');
    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sync($path, $source) {
    $s3cmd_path = $this->config->get('s3cmd_path');

    if (file_exists($s3cmd_path)) {
      $regions = $this->config->get('aws_regions');
      foreach ($regions as $code => $region) {
        if ($region['enabled'] == FALSE) {
          continue;
        }

        $options = array();

        foreach ($this->getExcludes() as $exclude) {
          $options[] = "--exclude '$exclude'";
        }

        if ($this->dry_run) {
          $options[] = '--dry-run';
        }

        if ($this->verbose) {
          $options[] = '--verbose';
        }

        $options[] = '--access_key ' . $this->getAccessKey();
        $options[] = '--secret_key ' . $this->getSecretKey();
        $options[] = '--region ' . $code;
        $options[] = '--delete-removed';
        $options[] = '--acl-public';

        try {
          $command = $s3cmd_path . ' sync ' . implode(' ', $options);

          shell_exec($command . ' ' . $path . $source . ' s3://' . $this->config->get('s3_bucket_name') . '/' . $source);

          return TRUE;
        }
        catch (Exception $e) {
          $this->logger->error($e->getMessage());

          return FALSE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludes() {
    $excludes = $this->config->get('s3cmd_excludes');
    if ($excludes) {
      return array_merge($excludes, self::$excludes);
    }
    else {
      return self::$excludes;
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getAccessKey() {
    return $this->settings->get('s3_access_key') ? $this->settings->get('s3_access_key') : $this->config->get('s3_access_key');
  }

  /**
   * {@inheritdoc}
   */
  private function getSecretKey() {
    return $this->settings->get('s3_secret_key') ? $this->settings->get('s3_secret_key') : $this->config->get('s3_secret_key');
  }
}
