<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Controller\Amazon_S3_SyncController.
 */

namespace Drupal\amazon_s3_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\amazon_s3_sync\Amazon_S3_SyncFileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for Amazon_S3_SyncController routes.
 */
class Amazon_S3_SyncController extends ControllerBase {

  /**
   * The Amazon_S3_Sync files manager.
   *
   * @var \Drupal\amazon_s3_sync\Amazon_S3_SyncFileManagerInterface
   */
  protected $Amazon_S3_SyncFileManager;

  /**
   * Constructs a Amazon_S3_SyncController object.
   *
   * @param \Drupal\amazon_s3_sync\Amazon_S3_SyncFileManagerInterface $amazon_s3_sync_files_manager
   *   The Amazon_S3_Sync files manager.
   */
  public function __construct(Amazon_S3_SyncFileManagerInterface $amazon_s3_sync_files_manager) {
    $this->Amazon_S3_SyncFileManager = $amazon_s3_sync_files_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('amazon_s3_sync.files_manager')
    );
  }
}
