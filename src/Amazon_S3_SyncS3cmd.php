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
   * @var string
   */
  private $region = 'us-east-1a';

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
    $this->addOption('--recursive');
    $this->addOption('--force');

    $this->setRegion($region_code);

    $this->addParameter($this->getBucket());

    $this->execute('del');
  }

  /**
   * {@inheritdoc}
   */
  public function sync($path, $source) {
    if (!$path || !$source) {
      return FALSE;
    }

    foreach ($this->getExcludes() as $exclude) {
      $this->addOption("--exclude '$exclude'");
    }

    $this->addOption('--delete-removed');
    $this->addOption('--acl-public');

    $this->addParameter($path . $source);
    $this->addParameter($this->getBucket() . '/' . $source);

    $regions = $this->config->get('aws_regions');
    foreach ($regions as $code => $region) {
      if ($region['enabled']) {
        $this->setRegion($code);

        $this->execute('sync');
      }
    }
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
      $this->addOption('--dry-run');
    }

    if ($this->verbose) {
      $this->addOption('--verbose');
    }

    $this->addOption('--access_key ' . $this->getAccessKey());
    $this->addOption('--secret_key ' . $this->getSecretKey());

    try {
      shell_exec($s3cmd_path .' '. $command .' '. $this->getRegion() .' '. $this->getOptions() .' '. $this->getParameters());

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
  public function addOption($value) {
    if (!in_array($value, $this->options)) {
      $this->options[] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addParameter($value) {
    if (!in_array($value, $this->parameters)) {
      $this->parameters[] = $value;
    }
    return $this;
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
  private function getExcludes() {
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
      return implode(' ', $this->options);
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
   * Return S3cmd required AWS region code.
   *
   * @return string
   */
  private function getRegion() {
    if ($this->region) {
      return "--region '" . $this->region . "'";
    }
  }

  /**
   * Define the S3cmd required AWS region code.
   *
   * @param string $code
   *   Region code.
   */
  private function setRegion($code) {
    if ($this->region != $code) {
      $this->region = $code;
    }
  }

  /**
   * Reset the internal state of S3cmd required values.
   */
  private function reset() {
    $this->parameters = array();
    $this->options    = array();
    $this->region     = NULL;
  }
}
