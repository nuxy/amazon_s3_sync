<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_Sync\Form\Amazon_S3_SyncConfigForm.
 */

namespace Drupal\amazon_s3_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Amazon_S3_SyncConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_s3_sync_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
    $config = \Drupal::configFactory()->getEditable('amazon_s3_sync.config');
    $config->set('', $form_state->getValue('settings'));
    $config->save();

    drupal_set_message(t('Configuration saved successfully!'), 'status', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amazon_s3_sync.config'];
  }
}
