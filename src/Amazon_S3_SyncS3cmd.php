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
   * @var array
   */
  private $options = array();

  /**
   * @var array
   */
  private $parameters = array();

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
    $this->config   = $config_factory->get('amazon_s3_sync.config');
    $this->settings = $settings;
    $this->logger   = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function empty($region_code) {
    $this->setOption('region', $region_code);
    $this->setOption('recursive');
    $this->setOption('force');

    $this->setParameter($this->getBucket());

    $this->execute('del');
  }

  /**
   * {@inheritdoc}
   */
  public function sync($path, $source) {
    if (!$path || !$source) {
      return FALSE;
    }

    $this->setOption('exclude', $this->getExcludeList());
    $this->setOption('delete-removed');
    $this->setOption('acl-public');

    $this->setParameter($path . $source);
    $this->setParameter($this->getBucket() . '/' . $source);

    $regions = $this->config->get('aws_regions');
    foreach ($regions as $code => $region) {
      if ($region['enabled']) {
        $this->setOption('region', $code);

        if (!$this->execute('sync')) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  private function execute($command) {
    $s3cmd_path = $this->config->get('s3cmd_path');
    if (!file_exists($s3cmd_path)) {
      return FALSE;
    }

    if ($this->dry_run) {
      $this->setOption('dry-run');
    }

    if ($this->verbose) {
      $this->setOption('verbose');
    }

    $this->setOption('access_key', $this->getAccessKey());
    $this->setOption('secret_key', $this->getSecretKey());

    try {
      shell_exec($s3cmd_path .' '. $command .' '. $this->getOptions() .' '. $this->getParameters());

      return TRUE;
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());

      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($key, $value = NULL) {
    if (!empty($key)) {
      $this->options[$key] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($value) {
    if (!empty($value)) {
      $this->parameters[] = $value;
    }
    return $this;
  }

  /**
   * Convert array key-values to S3cmd --argument format.
   *
   * @return array
   */
  private function formatOptions($array) {
    if (!empty($array)) {
      $args = array();
      foreach ($array as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $val) {
            $args[] = '--' . $key .' '. $val;
          }
        }
        else {
          $args[] = '--' . $key .' '. $value;
        }
      }
      return $args;
    }
  }

  /**
   * Return user defined bucket in AWS required format.
   *
   * @return string
   */
  private function getBucket() {
    $name = $this->config->get('s3_bucket_name');
    if ($name) {
      return 's3://' . $name;
    }
  }

  /**
   * Return merged exclude defaults with configuration defined values.
   *
   * @return array
   */
  private function getExcludeList() {
    $excludes = $this->config->get('s3cmd_excludes');
    if ($excludes) {
      return array_merge($excludes, self::$excludes);
    }
    else {
      return self::$excludes;
    }
  }

  /**
   * Return AWS access key from settings.php/configuration.
   *
   * @return string
   */
  private function getAccessKey() {
    return ($this->settings->get('s3_access_key'))
      ? $this->settings->get('s3_access_key')
      : $this->config->get('s3_access_key');
  }

  /**
   * Return AWS secret key from settings.php/configuration.
   *
   * @return string
   */
  private function getSecretKey() {
    return ($this->settings->get('s3_secret_key'))
      ? $this->settings->get('s3_secret_key')
      : $this->config->get('s3_secret_key');
  }

  /**
   * Return S3cmd required options as a concatenated string.
   *
   * @return string
   */
  private function getOptions() {
    if ($this->options) {
      return implode(' ', $this->formatOptions($this->options));
    }
  }

  /**
   * Return S3cmd required parameters as a concatenated string.
   *
   * @return string
   */
  private function getParameters() {
    if ($this->parameters) {
      return implode(' ', $this->parameters);
    }
  }

  /**
   * Reset the internal state of S3cmd required values.
   */
  private function reset() {
    $this->parameters = array();
    $this->options    = array();
  }
}
