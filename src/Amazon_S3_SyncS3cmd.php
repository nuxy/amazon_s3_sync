<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmd.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;

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
   * Constructs a Amazon_S3_SyncS3cmd object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings) {
    $this->configFactory = $config_factory;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function sync($source, $target) {
    $config = $this->configFactory->get('amazon_s3_sync.config');

    $s3cmd_path = $config->get('s3cmd_path');

    if (file_exists($s3cmd_path)) {
      $regions = $config->get('aws_regions');
      foreach ($regions as $code => $region) {
        $enabled = in_array($code, $config->get('aws_region'));
        if ($enabled == FALSE) {
          continue;
        }

        // Exclude system files.
        $options = array(
          "--exclude 'config__*'",
          "--exclude 'php/*'"
        );

        foreach ($config->get('s3cmd_excludes') as $exclude) {
          $options[] = "--exclude '$exclude'";
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
        //$options[] = '--delete-removed';
        $options[] = '--acl-public';

        try {
          $command = $s3cmd_path . ' sync ' . implode(' ', $options);

          shell_exec($command . ' ' . $source . ' s3://' . $config->get('s3_bucket_name') . '/' . $target);

          return TRUE;
        }
        catch (Exception $e) {
          echo $e->getMessage(), "\n";

          return FALSE;
        }
      }
    }
  }
}
