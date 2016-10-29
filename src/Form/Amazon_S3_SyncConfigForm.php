<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_Sync\Form\SettingsForm.
 */

namespace Drupal\amazon_s3_sync\Form;

use Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmdInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Amazon_S3_Sync settings.
 */
class Amazon_S3_SyncConfigForm extends ConfigFormBase {

  /**
   * The settings instance.
   *
   * var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The Amazon_S3_SyncS3cmd service.
   *
   * var \Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmdInterface
   */
  protected $s3cmd;

  /**
   * Constructs a Amazon_S3_SyncConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   * @param \Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmdInterface $s3cmd
   *   The Amazon_S3_SyncS3cmd service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings, Amazon_S3_SyncS3cmdInterface $s3cmd) {
    parent::__construct($config_factory);

    $this->settings = $settings;
    $this->s3cmd = $s3cmd;
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
      $container->get('amazon_s3_sync.s3cmd')
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
    $table_options = array();

    foreach ($config->get('aws_regions') as $code => $region) {
      $enabled = in_array($code, $config->get('aws_region') ?: array());

      $table_defaults[$code] = $enabled;

      $bucket_name = $config->get('s3_bucket_name');
      $endpoint = ($bucket_name && $enabled) ? "$bucket_name.{$region['endpoint']}" : $region['endpoint'];

      $table_options[$code] = array(
        'name' => $region['name'],
        'code' => $code,
        'endpoint' => $endpoint,
      );
    }

    $form['aws_region'] = array(
      '#type' => 'tableselect',
      '#header' => $table_header,
      '#options' => $table_options,
      '#default_value' => $table_defaults,
      '#multiple' => TRUE,
    );

    if ($config->get('aws_region') && $config->get('s3_bucket_name') && $config->get('s3cmd_path')) {
      $form['sync_files'] = array(
        '#type' => 'submit',
        '#value' => t('Sync files'),
        '#submit' => array('::submitSyncFiles'),
      );
    }

    $form['s3cmd'] = array(
      '#type' => 'details',
      '#title' => 's3cmd',
      '#description' => t('Command line tool for managing Amazon S3 and CloudFront services.'),
      '#open' => TRUE,
    );

    $s3cmd_exists = file_exists($config->get('s3cmd_path'));

    if (!$s3cmd_exists) {
      $project_url = Url::fromUri('https://github.com/s3tools/s3cmd');
      $link = \Drupal::l('https://github.com/s3tools/s3cmd', $project_url);
      drupal_set_message(t('The required dependency s3cmd is not installed. Please see @link for details.', array('@link' => $link)), 'error');
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
      $form['s3cmd']['path']['#attributes']['class'][] = 'error';
      $form['s3cmd']['path']['#attributes']['placeholder'] = $s3cmd_path_default;
    }

    if (!$this->settings->get('s3_bucket_name') || !$this->settings->get('s3_access_key') || !$this->settings->get('s3_secret_key')) {
      $form['amazon_s3'] = array(
        '#type' => 'details',
        '#title' => t('Simple Storage Service (S3)'),
        '#description' => t('For the security conscience the following values can be defined in the <em>settings.php</em>'),
        '#open' => TRUE,
      );

      if (!$this->settings->get('s3_bucket_name')) {
        $form['amazon_s3']['bucket_name'] = array(
          '#type' => 'textfield',
          '#title' => t('S3 bucket name'),
          '#description' => t('Must be a valid bucket that has <em>View Permissions</em> granted.'),
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

    if (!$this->settings->get('s3_bucket_name') &&
        !preg_match('/^[^\.\-]?[a-zA-Z0-9\.\-]{1,63}[^\.\-]?$/', $form_state->getValue('bucket_name'))) {
      $form_state->setErrorByName('bucket_name', t('The S3 bucket name entered is not valid.'));
    }

    if (!$this->settings->get('s3_access_key') &&
        !preg_match('/^[A-Z0-9]{20}$/', $form_state->getValue('access_key'))) {
      $form_state->setErrorByName('access_key', t('The S3 access key entered is not valid.'));
    }

    if (!$this->settings->get('s3_secret_key') &&
        !preg_match('/^[a-zA-Z0-9\+\/]{39,40}$/', $form_state->getValue('secret_key'))) {
      $form_state->setErrorByName('secret_key', t('The S3 secret key entered is not valid.'));
    }

    if (gethostbyname($form_state->getValue('common_name')) != $form_state->getValue('common_name')) {
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
      ->set('common_name', $form_state->getValue('common_name'));

    $regions = array();
    foreach ($form_state->getValue('aws_region') as $key => $value) {
      if ($value) {
        $regions[] = $value;
      }
    }
    $config->set('aws_region', $regions);

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
   * Synchronize files with the selected S3 buckets.
   *
   * {@inheritdoc}
   */
  public function submitSyncFiles(array &$form, FormStateInterface $form_state) {
    if ($this->s3cmd->sync()) {
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
