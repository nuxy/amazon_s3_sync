<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_Sync\Form\SettingsForm.
 */

namespace Drupal\amazon_s3_sync\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
      'name' => 'Region Name',
      'code' => 'Region',
      'endpoint' => 'Endpoint',
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

    $form['amazon_s3'] = array(
      '#type' => 'details',
      '#title' => 'Simple Storage Service (S3)',
      '#description' => '',
      '#open' => TRUE,
    );

    $form['amazon_s3']['bucket_name'] = array(
      '#type' => 'textfield',
      '#title' => 'S3 bucket name',
      '#description' => 'Must be a valid bucket that has <em>View Permissions</em> granted.',
      '#default_value' => $config->get('s3_bucket_name'),
      '#maxlength' => 63,
      '#required' => TRUE,
    );

    $form['s3cmd'] = array(
      '#type' => 'details',
      '#title' => 's3cmd',
      '#description' => '',
      '#open' => TRUE,
    );

    $form['s3cmd']['path'] = array(
      '#type' => 'textfield',
      '#title' => 'Path to binary',
      '#description' => 'Absolute path. This may vary based on the system set-up.',
      '#default_value' => $config->get('s3cmd_path'),
      '#maxlength' => 25,
      '#required' => TRUE,
    );

    $form['domain_name'] = array(
      '#title' => 'Domain name',
      '#description' => 'DNS configured domain name',
      '#default_value' => $config->get('domain_name'),
      '#maxlength' => 63,
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
      ->set('bucket_name', $form_state->getValue('bucket_name'))
      ->set('aws_region',  $form_state->getValue('aws_region'))
      ->set('s3cmd_path',  $form_state->getValue('s3cmd_path'));
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
