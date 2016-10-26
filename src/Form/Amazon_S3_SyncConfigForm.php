<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_Sync\Form\SettingsForm.
 */

namespace Drupal\amazon_s3_sync\Form;

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
   * Constructs a Amazon_S3_SyncConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config');

    $regions = $config->get('aws_regions');

    $header = array(
      'name' => t('Region Name'),
      'code' => t('Region'),
      'endpoint' => t('Endpoint'),
    );

    $options = array();

    foreach ($regions as $code => $region) {

      // @todo
      // Enable support for multiple regions.
      if ($code == 'us-east-1') {
        $options[$code] = array(
          'name' => $region['name'],
          'code' => $code,
          'endpoint' => $region['endpoint'],
        );
      }
    }

    $form['aws_region'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#required' => TRUE,
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

    $form['s3cmd']['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to binary'),
      '#description' => t('Must be an absolute path. This may vary based on your server set-up.'),
      '#default_value' => $config->get('s3cmd_path'),
      '#maxlength' => 25,
      '#required' => TRUE,
    );

    if (!$s3cmd_exists) {
      $form['s3cmd']['path']['#attributes']['class'][] = 'error';
    }

    if (!Settings::get('s3_bucket_name') || !Settings::get('s3_access_key') || !Settings::get('s3_secret_key')) {
      $form['amazon_s3'] = array(
        '#type' => 'details',
        '#title' => t('Simple Storage Service (S3)'),
        '#description' => t('For the security conscience the following values can be defined in the <em>settings.php</em>'),
        '#open' => TRUE,
      );

      if (!Settings::get('s3_bucket_name')) {
        $form['amazon_s3']['bucket_name'] = array(
          '#type' => 'textfield',
          '#title' => t('S3 bucket name'),
          '#description' => t('Must be a valid bucket that has <em>View Permissions</em> granted.'),
          '#default_value' => $config->get('s3_bucket_name'),
          '#maxlength' => 63,
          '#required' => TRUE,
        );
      }

      if (!Settings::get('s3_access_key')) {
        $form['amazon_s3']['access_key'] = array(
          '#type' => 'textfield',
          '#title' => t('Access Key'),
          '#description' => t('Enter your AWS access key.'),
          '#default_value' => $config->get('s3_access_key'),
          '#maxlength' => 20,
          '#required' => TRUE,
        );
      }

      if (!Settings::get('s3_secret_key')) {
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
      '#title' => t('Host name'),
      '#description' => t('Must be a fully qualified domain name.'),
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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config')
      ->set('aws_region',  $form_state->getValue('aws_region'))
      ->set('s3cmd_path',  $form_state->getValue('s3cmd_path'))
      ->set('common_name', $form_state->getValue('common_name'));

    if (!Settings::get('s3_bucket_name') || !Settings::get('s3_access_key') || !Settings::get('s3_secret_key')) {
      if ($config->get('s3_bucket_name')) {
        $config->set('s3_bucket_name', $form_state->getValue('bucket_name'));
      }

      if ($config->get('s3_access_key')) {
        $config->set('s3_access_key', $form_state->getValue('access_key'));
      }

      if ($config->get('s3_secret_key')) {
        $config->set('s3_secret_key', $form_state->getValue('secret_key'));
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amazon_s3_sync.config'];
  }
}
