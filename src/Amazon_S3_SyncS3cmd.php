<?php

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;

/**
 * Defines a Amazon_S3_Sync s3cmd object.
 */
// @codingStandardsIgnoreLine
class Amazon_S3_SyncS3cmd implements Amazon_S3_SyncS3cmdInterface {

  /**
   * Simulate sync process without syncing files.
   *
   * @var bool
   */
  public $dryRun = FALSE;

  /**
   * Enable logger debug messages.
   *
   * @var bool
   */
  public $debug = FALSE;

  /**
   * Enable verbose debug messages.
   *
   * @var bool
   */
  public $verbose = FALSE;

  /**
   * List of files that should never by synced.
   *
   * @var array
   */
  public static $excludes = [
    '.htaccess',
    '*.php',
    '*.yml',
    'README.txt',
    'config__*',
    'logs',
    'private',
  ];

  /**
   * Configuration instance.
   *
   * @var object
   */
  private $config;

  /**
   * List of S3cmd [options].
   *
   * @var array
   */
  private $options = [];

  /**
   * List of S3cmd [parameters].
   *
   * @var array
   */
  private $parameters = [];

  /**
   * The settings instance.
   *
   * @var \Drupal\Core\Site\Settings
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
  // @codingStandardsIgnoreLine
  public function empty($region_code) {
    if (empty($region_code)) {
      return FALSE;
    }

    $this->setOption('region', $region_code);
    $this->setOption('recursive');
    $this->setOption('force');

    $this->setParameter($this->getBucket());

    return $this->execute('del');
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public function delete($target) {
    if (empty($target)) {
      return FALSE;
    }

    $this->setParameter($this->getBucket() . DIRECTORY_SEPARATOR . $target);

    return $this->updateRegions(function () {
      return $this->execute('del');
    });
  }

  /**
   * {@inheritdoc}
   */
  public function sync($source, $target = NULL) {
    if (empty($source)) {
      return FALSE;
    }

    $this->setOption('exclude', $this->getExcludeList());
    $this->setOption('delete-removed');
    $this->setOption('acl-public');
    $this->setOption('no-mime-magic');
    $this->setOption('stop-on-error');
    $this->setOption('stats');
    $this->setOption('force');

    $this->setParameter($source);
    $this->setParameter($this->getBucket() . DIRECTORY_SEPARATOR . $target);

    return $this->updateRegions(function () {
      return $this->execute();
    });
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
   * Execute an S3cmd remote operation.
   *
   * @param string $command
   *   Command to run (default: sync).
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  private function execute($command = 'sync') {
    $s3cmd_path = $this->config->get('s3cmd_path');
    if (!file_exists($s3cmd_path)) {
      return FALSE;
    }

    $debug = FALSE;

    if ($this->dryRun) {
      $this->setOption('dry-run');
      $debug = TRUE;
    }

    if ($this->debug) {
      $this->setOption('debug');
      $debug = TRUE;
    }

    if ($this->verbose) {
      $this->setOption('verbose');
      $debug = TRUE;
    }

    $this->setOption('access_key', $this->getAccessKey());
    $this->setOption('secret_key', $this->getSecretKey());

    try {
      exec($s3cmd_path . ' ' . $this->getOptions() . ' ' . $command . ' ' . $this->getParameters() . ' 2>&1', $output);

      if (!empty($output)) {
        $message = implode('<br>', $output);

        if ($debug) {
          $this->logger->debug($message);
        }
        else {
          $this->logger->notice($message);
        }
      }

      return TRUE;
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());

      return FALSE;
    }
  }

  /**
   * Convert array key-values to S3cmd --argument format.
   *
   * @param array $array
   *   Associative array of options.
   * @param array &$args
   *   Reference to formOptions arguments (optional).
   * @param string $name
   *   Option name (optional).
   *
   * @return array
   *   List of arguments.
   */
  private function formatOptions(array $array, array &$args = [], $name = NULL) {
    if (!empty($array)) {
      foreach ($array as $key => $value) {
        $key = $name ?: $key;

        // Process values with duplicate keys.
        if (is_array($value)) {
          $this->formatOptions($value, $args, $key);

          continue;
        }

        // Escape wildcard values.
        if ($value && preg_match('/\*/', $value)) {
          $value = escapeshellarg($value);
        }

        $args[] = trim('--' . $key . ' ' . $value);
      }
      return $args;
    }
  }

  /**
   * Return user defined bucket in AWS required format.
   *
   * @return string
   *   Bucket name.
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
   *   List of files to exclude.
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
   *   AWS access key.
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
   *   AWS secret.
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
   *   Options in string format.
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
   *   Parameters in string format.
   */
  private function getParameters() {
    if ($this->parameters) {
      return implode(' ', $this->parameters);
    }
  }

  /**
   * Run callback-based operations across enabled regions.
   *
   * @param callable $callback
   *   Callback function.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  private function updateRegions(callable $callback) {
    $regions = $this->config->get('aws_regions');
    foreach ($regions as $code => $region) {
      if ($region['enabled']) {
        $this->setOption('region', $code);

        if (!$callback()) {
          return FALSE;
        }
      }
    }

    $this->reset();

    return TRUE;
  }

  /**
   * Reset the internal state of S3cmd required values.
   */
  private function reset() {
    $this->parameters = [];
    $this->options    = [];
  }

}
