<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Form\Amazon_S3_SyncConfigForm.
 */

namespace Drupal\amazon_s3_sync\Form;

use Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmd;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Amazon_S3_Sync settings.
 */
class Amazon_S3_SyncConfigForm extends ConfigFormBase {

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
   * Constructs a Amazon_S3_SyncConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings, LoggerInterface $logger) {
    parent::__construct($config_factory);

    $this->settings = $settings;
    $this->logger   = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_s3_sync_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('settings'),
      $container->get('logger.channel.s3cmd')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config');

    $table_header = array(
      'name' => t('Region Name'),
      'code' => t('Region'),
      'endpoint' => t('Endpoint'),
    );

    $table_defaults = array();
    $table_options  = array();
    $table_states   = array();

    $bucket_name = $config->get('s3_bucket_name');

    foreach ($config->get('aws_regions') as $code => $region) {
      $endpoint = $region['endpoint'];
      $enabled = $region['enabled'];

      $table_defaults[$code] = $enabled;

      $table_options[$code] = array(
        'name' => $region['name'],
        'code' => $code,
        'endpoint' => $endpoint,
      );

      if ($bucket_name && $enabled) {
        $table_options[$code]['endpoint'] = $bucket_name . '.' . $endpoint;
        $table_options[$code]['#attributes']['class'][] = 'selected';
      }

      $table_states[] = array('input[name="aws_regions[' . $code . ']"]' => array('checked' => TRUE));
    }

    $form['aws_regions'] = array(
      '#type' => 'tableselect',
      '#header' => $table_header,
      '#options' => $table_options,
      '#default_value' => $table_defaults,
      '#multiple' => TRUE,
    );

    $s3cmd_path = $config->get('s3cmd_path');

    if ($bucket_name && $s3cmd_path) {
      $form['sync_files'] = array(
        '#type' => 'submit',
        '#value' => t('Sync files'),
        '#submit' => array('::submitSyncFiles'),
        '#states' => array(
          'visible' => $table_states,
        ),
      );
    }

    $form['s3cmd'] = array(
      '#type' => 'details',
      '#title' => 's3cmd',
      '#description' => t('Command line tool for managing Amazon S3 and CloudFront services.'),
      '#open' => TRUE,
    );

    $s3cmd_exists = file_exists($s3cmd_path);

    if (!$s3cmd_exists) {

      // Create link to S3cmd Github project.
      $s3cmd_url = 'https://github.com/s3tools/s3cmd';
      $s3cmd_obj = Url::fromUri($s3cmd_url);
      $s3cmd_link = \Drupal::l($s3cmd_url, $s3cmd_obj);

      drupal_set_message(t('The required dependency s3cmd is not installed. Please see @link for details.', array('@link' => $s3cmd_link)), 'error');
    }

    $s3cmd_path_default = '/usr/bin/s3cmd';

    $form['s3cmd']['s3cmd_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to binary'),
      '#description' => t('Must be an absolute path. This may vary based on your server set-up.'),
      '#default_value' => (file_exists($s3cmd_path_default)) ? $s3cmd_path_default : $config->get('s3cmd_path'),
      '#maxlength' => 25,
      '#required' => TRUE,
    );

    if (!$s3cmd_exists) {
      $form['s3cmd']['s3cmd_path']['#attributes']['class'][] = 'error';
      $form['s3cmd']['s3cmd_path']['#attributes']['placeholder'] = $s3cmd_path_default;
    }

    $form['s3cmd']['s3cmd_excludes'] = array(
      '#type' => 'textfield',
      '#title' => t('Excludes'),
      '#description' => t('Filenames and paths matching GLOB will be excluded. <strong class="color-warning">Warning</strong> Private files that exist in the <em>Public file system path</em> should be added here.'),
      '#default_value' => $config->get('s3cmd_excludes'),
      '#maxlength' => 255,
      '#required' => FALSE,
      '#attributes' => array(
        'placeholder' => 'directory/* image.* image.jpg',
      ),
    );

    $form['s3cmd']['options'] = array(
      '#type' => 'container',
      '#prefix' => '<strong>' . t('Script options') . '</strong>',
    );

    $form['s3cmd']['options']['dry_run'] = array(
      '#type' => 'checkbox',
      '#title' => t('Simulate the upload operation without touching the S3 bucket.'),
      '#default_value' => ($config->get('dry_run')) ? TRUE : FALSE,
    );

    $form['s3cmd']['options']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debug output. <strong class="color-warning">Warning</strong> AWS Security Credentials will be exposed in the log output.</strong>'),
      '#default_value' => ($config->get('debug')) ? TRUE : FALSE,
      '#states' => array(
        'disabled' => array('input[name="verbose"]' => array('checked' => TRUE)),
      ),
    );

    $form['s3cmd']['options']['verbose'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable verbose output.'),
      '#default_value' => ($config->get('verbose')) ? TRUE : FALSE,
      '#states' => array(
        'disabled' => array('input[name="debug"]' => array('checked' => TRUE)),
      ),
    );

    if (!$this->settings->get('s3_bucket_name') || !$this->settings->get('s3_access_key') || !$this->settings->get('s3_secret_key')) {

      // Create link to "AWS Security Credentials".
      $aws_url1 = 'http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSGettingStartedGuide/AWSCredentials.html';
      $aws_obj1 = Url::fromUri($aws_url1);
      $aws_link1 = \Drupal::l(t('AWS Security Credentials'), $aws_obj1);

      // Create link to "Working with Amazon S3 Buckets".
      $aws_url2 = 'http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingBucket.html';
      $aws_obj2 = Url::fromUri($aws_url2);
      $aws_link2 = \Drupal::l('S3 Bucket', $aws_obj2);

      $form['amazon_s3'] = array(
        '#type' => 'details',
        '#title' => t('Simple Storage Service (S3)'),
        '#description' => t('Setup your @link1 and @link2 below. This can also be defined in <em>settings.php</em>', array('@link1' => $aws_link1, '@link2' => $aws_link2)),
        '#open' => TRUE,
      );

      if (!$this->settings->get('s3_bucket_name')) {
        $form['amazon_s3']['bucket_name'] = array(
          '#type' => 'textfield',
          '#title' => t('S3 bucket name'),
          '#description' => t('Must be an empty bucket that has <em>View Permissions</em> granted.'),
          '#default_value' => $config->get('s3_bucket_name'),
          '#maxlength' => 63,
          '#required' => TRUE,
        );
      }

      if (!$this->settings->get('s3_access_key')) {
        $form['amazon_s3']['access_key'] = array(
          '#type' => 'textfield',
          '#title' => t('Access Key'),
          '#description' => t('Enter your AWS access key.'),
          '#default_value' => $config->get('s3_access_key'),
          '#maxlength' => 20,
          '#required' => TRUE,
        );
      }

      if (!$this->settings->get('s3_secret_key')) {
        $form['amazon_s3']['secret_key'] = array(
          '#type' => 'textfield',
          '#title' => t('Secret Key'),
          '#description' => t('Enter your AWS secret key.'),
          '#default_value' => $config->get('s3_secret_key'),
          '#maxlength' => 40,
          '#required' => TRUE,
        );
      }
    }

    $form['virtual_hosting'] = array(
      '#type' => 'details',
      '#title' => t('Virtual Hosting Buckets'),
      '#description' => t('If your bucket name and domain name are <em>files.drupal.com</em>, the CNAME record should alias <em>files.drupal.com.s3.amazonaws.com</em>'),
      '#open' => TRUE,
    );

    $form['virtual_hosting']['common_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Common name'),
      '#description' => t('Must be a fully qualified host name.'),
      '#default_value' => $config->get('common_name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!file_exists($form_state->getValue('s3cmd_path'))) {
      $form_state->setErrorByName('s3cmd_path', t('The path to the s3cmd binary does not exist.'));
    }

    if (!empty($form_state->getValue('s3cmd_excludes')) &&
        !preg_match('/^[\w\s-\.\/\*]*$/', $form_state->getValue('s3cmd_excludes'))) {
      $form_state->setErrorByName('s3cmd_excludes', t('Excludes must contain a valid filename and be separated by spaces.'));
    }

    if (!$this->settings->get('s3_bucket_name') &&
        !preg_match('/^[^\.\-]?[a-zA-Z0-9\.\-]{1,63}[^\.\-]?$/', $form_state->getValue('bucket_name'))) {
      $form_state->setErrorByName('bucket_name', t('The S3 bucket name entered is not valid.'));
    }

    if ($this->settings->get('s3_access_key') &&
        !preg_match('/^[A-Z0-9]{20}$/', $form_state->getValue('access_key'))) {
      $form_state->setErrorByName('access_key', t('The S3 access key entered is not valid.'));
    }

    if (!$this->settings->get('s3_secret_key') &&
        !preg_match('/^[a-zA-Z0-9\+\/]{39,40}$/', $form_state->getValue('secret_key'))) {
      $form_state->setErrorByName('secret_key', t('The S3 secret key entered is not valid.'));
    }

    if (gethostbyname($form_state->getValue('common_name')) == $form_state->getValue('common_name')) {
      $form_state->setErrorByName('common_name', t('The common name entered is not a valid CNAME record.'));
    }

    drupal_get_messages('error');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config')
      ->set('s3cmd_path',  $form_state->getValue('s3cmd_path'))
      ->set('common_name', $form_state->getValue('common_name'))
      ->set('dry_run',     $form_state->getValue('dry_run'))
      ->set('debug',       $form_state->getValue('debug'))
      ->set('verbose',     $form_state->getValue('verbose'));

    if ($form_state->getValue('s3cmd_excludes')) {
      $excludes = explode(' ', $form_state->getValue('s3cmd_excludes'));
      $config->set('s3cmd_excludes', $excludes);
    }

    foreach ($form_state->getValue('aws_regions') as $key => $value) {

      // Empty bucket contents for unselected regions..
      if (isset($form['aws_regions']['#options'][$key]['#attributes']) && !$value) {

        // .. using the S3cmd client.
        $s3cmd = new Amazon_S3_SyncS3cmd($this->configFactory, $this->settings, $this->logger);
        $s3cmd->dry_run = $config->get('dry_run');
        $s3cmd->debug   = $config->get('debug');
        $s3cmd->verbose = $config->get('verbose');
        $s3cmd->empty($key);

        $message = t('Deleting contents of Amazon S3 bucket (@bucket_name) in region (@region)', array('@bucket_name' => $config->get('s3_bucket_name'), '@region' => $key));

        $this->logger->notice($message);

        $config->set('aws_regions.' . $key . '.enabled', FALSE);
      }
      else {
        $config->set('aws_regions.' . $key . '.enabled', $value);
      }
    }

    if (!$this->settings->get('s3_bucket_name')) {
      $config->set('s3_bucket_name', $form_state->getValue('bucket_name'));
    }

    if (!$this->settings->get('s3_access_key')) {
      $config->set('s3_access_key', $form_state->getValue('access_key'));
    }

    if (!$this->settings->get('s3_secret_key')) {
      $config->set('s3_secret_key', $form_state->getValue('secret_key'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitSyncFiles(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config');

    $path = DRUPAL_ROOT . '/' . PublicStream::basePath() . '/';

    $operations = array();

    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $file => $object) {
      $source = str_replace($path, '', $file);

      if (preg_match('/[\.]{1,2}$/', $source)) {
        continue;
      }

      $operations[] = array(
        '\Drupal\amazon_s3_sync\Form\Amazon_S3_SyncConfigForm::submitSyncFilesBatch', array($this, $path, $source),
      );
    }

    $message = t('Updating Amazon S3 buckets (@bucket_name)', array('@bucket_name' => $config->get('s3_bucket_name')));

    batch_set(array(
      'title' => $message,
      'progress_message' => t('Processed @current of @total files.'),
      'operations' => $operations,
      'finished' => array(get_class($this), 'submitSyncFilesCallback'),
    ));

    $this->logger->notice($message);
  }

  /**
   * Batch process to synchronize files with the selected S3 buckets.
   *
   * @param \Drupal\amazon_s3_sync\Form\Amazon_S3_SyncConfigForm $self
   *   Reference to this class object.
   * @param string $path
   *   Absolute path to the local $source file.
   * @param string $source
   *   Filename or path to upload to the S3.
   * @param string $context
   *   Status information about the current batch.
   */
  public static function submitSyncFilesBatch(Amazon_S3_SyncConfigForm $self, $path, $source, &$context) {
    $config = $self->config('amazon_s3_sync.config');

    // Sync each file using the S3cmd client.
    $s3cmd = new Amazon_S3_SyncS3cmd($self->configFactory, $self->settings, $self->logger);
    $s3cmd->dry_run = $config->get('dry_run');
    $s3cmd->debug   = $config->get('debug');
    $s3cmd->verbose = $config->get('verbose');
    $s3cmd->sync($path, $source);
  }

  /**
   * Batch process callback.
   *
   * {@inheritdoc}
   */
  public static function submitSyncFilesCallback($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t('Files synchronized to S3 bucket successfully.'));
    }
    else {
      drupal_set_message(t('Failed to synchronize files to S3 bucket.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amazon_s3_sync.config'];
  }
}
