<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmd.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;

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
  public function sync() {
    $config = $this->configFactory->get('amazon_s3_sync.config');

    $s3cmd_path = $config->get('s3cmd_path');

    if (file_exists($s3cmd_path)) {
      $regions = $config->get('aws_regions');
      foreach ($regions as $code => $region) {
        $enabled = in_array($code, $config->get('aws_region'));
        if ($enabled == FALSE) {
          continue;
        }

        $script_opts = array(
          "--exclude 'config__*'",
          "--exclude 'php/*'"
        );

        foreach ($config->get('s3cmd_excludes') as $exclude) {
          $script_opts[] = "--exclude '$exclude'";
        }

        if ($this->dry_run) {
          $script_opts[] = '--dry-run';
        }

        if ($this->verbose) {
          $script_opts[] = '--verbose';
        }

        $access_key = $this->settings->get('s3_access_key') ? $this->settings->get('s3_access_key') : $config->get('s3_access_key');
        $secret_key = $this->settings->get('s3_secret_key') ? $this->settings->get('s3_secret_key') : $config->get('s3_secret_key');

        $script_opts[] = '--access_key ' . $access_key;
        $script_opts[] = '--secret_key ' . $secret_key;
        $script_opts[] = '--region ' . $code;

        try {
          $command = $s3cmd_path . ' sync ' . implode(' ', $script_opts);
          $source  = DRUPAL_ROOT . '/' . PublicStream::basePath();
          $target  = 's3://' . $config->get('s3_bucket_name');

          shell_exec($command .' '. $source .' '. $target);

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
