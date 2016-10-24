<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncFileManager.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Defines a Amazon_S3_Sync file manager.
 */
class Amazon_S3_SyncFileManager implements Amazon_S3_SyncFileManagerInterface {
  use StringTranslationTrait;

  /**
   * Amazon_S3_Sync file storage.
   *
   * @var \Drupal\amazon_s3_sync\Amazon_S3_SyncFileStorageInterface
   */
  protected $amazon_s3_syncFileStorage;

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
   * Constructs a Amazon_S3_SyncFileManager object.
   *
   * @param \Drupal\amazon_s3_sync\Amazon_S3_SyncFileStorageInterface $amazon_s3_sync_file_storage
   *   The Amazon_S3_SyncFile storage.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(Amazon_S3_SyncFileStorageInterface $amazon_s3_sync_file_storage, TranslationInterface $translation, RendererInterface $renderer) {
    $this->amazon_s3_syncFileStorage = $amazon_s3_sync_file_storage;
    $this->stringTranslation = $translation;
    $this->renderer = $renderer;
  }
}
