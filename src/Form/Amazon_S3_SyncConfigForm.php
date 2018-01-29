<?php

namespace Drupal\amazon_s3_sync\Form;

use Drupal\amazon_s3_sync\Amazon_S3_SyncS3cmd;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Amazon_S3_Sync settings.
 */
// @codingStandardsIgnoreLine
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

    $bucket_name = $config->get('s3_bucket_name');

    $configured = ($bucket_name) ? TRUE : FALSE;

    $table_header = [
      'name' => t('Region Name'),
      'code' => t('Region'),
      'endpoint' => t('Endpoint'),
    ];

    $table_defaults = [];
    $table_options  = [];
    $table_states   = [];

    foreach ($config->get('aws_regions') as $code => $region) {
      $endpoint = $region['endpoint'];
      $enabled = $region['enabled'];

      $table_defaults[$code] = $enabled;

      $table_options[$code] = [
        'name' => $region['name'],
        'code' => $code,
        'endpoint' => $endpoint,
      ];

      if ($bucket_name && $enabled) {
        $table_options[$code]['endpoint'] = $bucket_name . '.' . $endpoint;
        $table_options[$code]['#attributes']['class'][] = 'selected';
      }

      $table_states[] = ['input[name="aws_regions[' . $code . ']"]' => ['checked' => TRUE]];
    }

    // Attach custom JavaScript to the form.
    $form['#attached'] = [
      'library' => ['amazon_s3_sync/amazon_s3_sync'],
    ];

    $form['aws_regions'] = [
      '#type' => 'tableselect',
      '#header' => $table_header,
      '#options' => $table_options,
      '#default_value' => $table_defaults,
      '#empty' => t('No availability zones available.'),
      '#multiple' => TRUE,
      '#access' => $configured,
    ];

    $s3cmd_path = $config->get('s3cmd_path');
    $s3cmd_exists = file_exists($s3cmd_path);

    $form['sync_files'] = [
      '#type' => 'submit',
      '#value' => t('Sync files'),
      '#submit' => ['::submitSyncFiles'],
      '#states' => [
        'visible' => $table_states,
      ],
      '#access' => $s3cmd_exists,
    ];

    $form['s3cmd'] = [
      '#type' => 'details',
      '#title' => 's3cmd',
      '#description' => t('Command line tool for managing Amazon S3 and CloudFront services.'),
      '#open' => TRUE,
    ];

    if (!$s3cmd_exists) {

      // Create link to S3cmd Github project.
      $s3cmd_url = 'https://github.com/s3tools/s3cmd';
      $s3cmd_link = Link::fromTextAndUrl($s3cmd_url, Url::fromUri($s3cmd_url, array()))->toString();

      drupal_set_message(t('The required dependency s3cmd is not installed. Please see @link for details.', ['@link' => $s3cmd_link]), 'error');
    }

    $s3cmd_path_default = '/usr/bin/s3cmd';

    $form['s3cmd']['s3cmd_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to binary'),
      '#description' => t('Must be an absolute path. This may vary based on your server set-up.'),
      '#default_value' => (file_exists($s3cmd_path_default)) ? $s3cmd_path_default : $config->get('s3cmd_path'),
      '#maxlength' => 25,
      '#required' => TRUE,
    ];

    if (!$s3cmd_exists) {
      $form['s3cmd']['s3cmd_path']['#attributes']['class'][] = 'error';
      $form['s3cmd']['s3cmd_path']['#attributes']['placeholder'] = $s3cmd_path_default;
    }

    $form['s3cmd']['s3cmd_excludes'] = [
      '#type' => 'textfield',
      '#title' => t('Excludes'),
      '#description' => t('Filenames and paths matching GLOB will be excluded. <strong class="color-warning">Warning:</strong> Private files that exist in the <em>Public file system path</em> should be added here.'),
      '#default_value' => $config->get('s3cmd_excludes'),
      '#maxlength' => 255,
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => 'directory/* image.* image.jpg',
      ],
    ];

    $form['s3cmd']['options'] = [
      '#type' => 'container',
      '#prefix' => '<strong>' . t('Script options') . '</strong>',
      '#access' => $configured,
    ];

    $form['s3cmd']['options']['dry_run'] = [
      '#type' => 'checkbox',
      '#title' => t('Simulate the upload operation without touching the S3 bucket.'),
      '#default_value' => ($config->get('dry_run')) ? TRUE : FALSE,
    ];

    $form['s3cmd']['options']['debug'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable debug output. <strong class="color-warning">Warning:</strong> AWS Security Credentials will be exposed in the log output.</strong>'),
      '#default_value' => ($config->get('debug')) ? TRUE : FALSE,
      '#states' => [
        'disabled' => ['input[name="verbose"]' => ['checked' => TRUE]],
      ],
    ];

    $form['s3cmd']['options']['verbose'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable verbose output.'),
      '#default_value' => ($config->get('verbose')) ? TRUE : FALSE,
      '#states' => [
        'disabled' => ['input[name="debug"]' => ['checked' => TRUE]],
      ],
    ];

    if (!$this->settings->get('s3_bucket_name') || !$this->settings->get('s3_access_key') || !$this->settings->get('s3_secret_key')) {

      // Create link to "AWS Security Credentials".
      $aws_url1 = 'http://docs.aws.amazon.com/general/latest/gr/aws-security-credentials.html';
      $aws_link1 = Link::fromTextAndUrl(t('AWS Security Credentials'), Url::fromUri($aws_url1, array()))->toString();

      // Create link to "Working with Amazon S3 Buckets".
      $aws_url2 = 'http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingBucket.html';
      $aws_link2 = Link::fromTextAndUrl(t('S3 Bucket'), Url::fromUri($aws_url2, array()))->toString();

      $form['amazon_s3'] = [
        '#type' => 'details',
        '#title' => t('Simple Storage Service (S3)'),
        '#description' => t('Setup your @link1 and @link2 below. This can also be defined in <em>settings.php</em>', ['@link1' => $aws_link1, '@link2' => $aws_link2]),
        '#open' => TRUE,
      ];

      if (!$this->settings->get('s3_bucket_name')) {
        $form['amazon_s3']['bucket_name'] = [
          '#type' => 'textfield',
          '#title' => t('S3 bucket name'),
          '#description' => t('Must be an empty bucket that has <em>View Permissions</em> granted.'),
          '#default_value' => $config->get('s3_bucket_name'),
          '#maxlength' => 63,
          '#required' => TRUE,
        ];
      }

      if (!$this->settings->get('s3_access_key')) {
        $form['amazon_s3']['access_key'] = [
          '#type' => 'textfield',
          '#title' => t('Access Key'),
          '#description' => t('Enter your AWS access key.'),
          '#default_value' => $config->get('s3_access_key'),
          '#maxlength' => 20,
          '#required' => TRUE,
        ];
      }

      if (!$this->settings->get('s3_secret_key')) {
        $form['amazon_s3']['secret_key'] = [
          '#type' => 'textfield',
          '#title' => t('Secret Key'),
          '#description' => t('Enter your AWS secret key.'),
          '#default_value' => $config->get('s3_secret_key'),
          '#maxlength' => 40,
          '#required' => TRUE,
        ];
      }
    }

    $form['virtual_hosting'] = [
      '#type' => 'details',
      '#title' => t('Virtual Hosting'),
      '#description' => t('If your bucket name and domain name are <em>files.drupal.com</em>, the CNAME record should alias <em>files.drupal.com</em>.s3.amazonaws.com'),
      '#open' => TRUE,
    ];

    $form['virtual_hosting']['common_name'] = [
      '#type' => 'textfield',
      '#title' => t('Common name'),
      '#description' => t('Must be a fully qualified domain name.'),
      '#default_value' => $config->get('common_name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['virtual_hosting']['endpoint'] = [
      '#type' => 'select',
      '#title' => t('Endpoint'),
      '#description' => t('Must reference a region that is currently enabled.'),
      '#default_value' => $config->get('endpoint'),
      '#options' => array_column($table_options, 'endpoint', 'endpoint'),
      '#states' => [
        'visible' => [
          $table_states,

          // @codingStandardsIgnoreLine
          'input[name="rewrite_url"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => $configured,
    ];

    $form['virtual_hosting']['options'] = [
      '#type' => 'container',
      '#prefix' => '<strong>' . t('Website options') . '</strong>',
      '#access' => $configured,
    ];

    $form['virtual_hosting']['options']['rewrite_url'] = [
      '#type' => 'checkbox',
      '#title' => t('Rewrite URLs for <em>public://</em> files to the Common name above. <strong class="color-warning">Warning:</strong> S3cmd excludes will be ignored.'),
      '#default_value' => ($config->get('rewrite_url')) ? TRUE : FALSE,
      '#states' => [
        'enabled' => [$table_states],
      ],
    ];

    // @codingStandardsIgnoreStart
    /*$form['virtual_hosting']['options']['enable_ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable SSL on public file requests'),
      '#default_value' => ($config->get('enable_ssl')) ? TRUE : FALSE,
      '#states' => [
        'disabled' => ['input[name="rewrite_url"]' => ['checked' => FALSE]],
      ],
    ];*/
    // @codingStandardsIgnoreEnd

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

    $common_name = $form_state->getValue('common_name');

    if (gethostbyname($common_name) == $common_name) {
      $form_state->setErrorByName('common_name', t('The common name entered is not a valid CNAME record.'));
    }

    drupal_get_messages('error');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amazon_s3_sync.config')
      ->set('s3cmd_path', $form_state->getValue('s3cmd_path'))
      ->set('common_name', $form_state->getValue('common_name'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('rewrite_url', $form_state->getValue('rewrite_url'))
      ->set('enable_ssl', $form_state->getValue('enable_ssl'))
      ->set('dry_run', $form_state->getValue('dry_run'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('verbose', $form_state->getValue('verbose'));

    if ($form_state->getValue('s3cmd_excludes')) {
      $excludes = explode(' ', $form_state->getValue('s3cmd_excludes'));
      $config->set('s3cmd_excludes', $excludes);
    }

    foreach ($form_state->getValue('aws_regions') as $key => $value) {

      // Empty bucket contents for unselected regions..
      if (isset($form['aws_regions']['#options'][$key]['#attributes']) && !$value) {
        $message = t('Deleting contents of Amazon S3 bucket (@bucket_name) in region (@region)', ['@bucket_name' => $config->get('s3_bucket_name'), '@region' => $key]);

        $this->logger->notice($message);

        // .. using the S3cmd client.
        $s3cmd = new Amazon_S3_SyncS3cmd($this->configFactory, $this->settings, $this->logger);
        $s3cmd->dry_run = $config->get('dry_run');
        $s3cmd->debug = $config->get('debug');
        $s3cmd->verbose = $config->get('verbose');
        $s3cmd->empty($key);

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

    $path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . PublicStream::basePath() . DIRECTORY_SEPARATOR;

    $operations[] = [
      '\Drupal\amazon_s3_sync\Form\Amazon_S3_SyncConfigForm::submitSyncFilesBatch', [$this, $path],
    ];

    $message = t('Updating Amazon S3 bucket (@bucket_name) selected regions.', ['@bucket_name' => $config->get('s3_bucket_name')]);

    batch_set([
      'title' => t('Amazon S3 sync'),
      'init_message' => $message,
      'progress_message' => t('Processing..'),
      'operations' => $operations,
      'finished' => [get_class($this), 'submitSyncFilesCallback'],
    ]);

    $this->logger->notice($message);
  }

  /**
   * Batch process to synchronize files with the selected S3 buckets.
   *
   * @param \Drupal\amazon_s3_sync\Form\Amazon_S3_SyncConfigForm $self
   *   Reference to this class object.
   * @param string $source
   *   Absolute path to the file or directory.
   * @param string $context
   *   Status information about the current batch.
   */
  public static function submitSyncFilesBatch(Amazon_S3_SyncConfigForm $self, $source, &$context) {
    $config = $self->config('amazon_s3_sync.config');

    // Sync each file using the S3cmd client.
    $s3cmd = new Amazon_S3_SyncS3cmd($self->configFactory, $self->settings, $self->logger);
    $s3cmd->dry_run = $config->get('dry_run');
    $s3cmd->debug = $config->get('debug');
    $s3cmd->verbose = $config->get('verbose');
    $s3cmd->sync($source);
  }

  /**
   * Batch process callback.
   *
   * {@inheritdoc}
   */
  public static function submitSyncFilesCallback($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t('Files synchronized successfully.'));
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
