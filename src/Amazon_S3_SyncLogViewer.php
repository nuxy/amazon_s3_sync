<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncLogViewer.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Defines a Amazon_S3_Sync log viewer.
 */
class Amazon_S3_SyncLogViewer implements Amazon_S3_SyncLogViewerInterface {
  use StringTranslationTrait;

  /**
   * Amazon_S3_Sync log storage.
   *
   * @var \Drupal\amazon_s3_sync\Amazon_S3_SyncLogStorageInterface
   */
  protected $amazon_s3_syncLogStorage;

  /**
   * The string translation service.
   *
   * @var  \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a Amazon_S3_SyncLogViewer object.
   *
   * @param \Drupal\amazon_s3_sync\Amazon_S3_SyncLogStorageInterface $amazon_s3_sync_log_storage
   *   The Amazon_S3_SyncLog storage.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(Amazon_S3_SyncLogStorageInterface $amazon_s3_sync_log_storage, TranslationInterface $translation, RendererInterface $renderer) {
    $this->amazon_s3_syncLogStorage = $amazon_s3_sync_log_storage;
    $this->stringTranslation = $translation;
    $this->renderer = $renderer;
  }
}
