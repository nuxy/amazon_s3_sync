<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Controller\Amazon_S3_SyncController.
 */

namespace Drupal\amazon_s3_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\amazon_s3_sync\Amazon_S3_SyncLogViewerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for Amazon_S3_SyncController routes.
 */
class Amazon_S3_SyncController extends ControllerBase {

  /**
   * The Amazon_S3_Sync log viewer.
   *
   * @var \Drupal\amazon_s3_sync\Amazon_S3_SyncLogViewerInterface
   */
  protected $Amazon_S3_SyncLogViewer;

  /**
   * Constructs a Amazon_S3_SyncController object.
   *
   * @param \Drupal\amazon_s3_sync\Amazon_S3_SyncLogViewerInterface $amazon_s3_sync_log_viewer
   *   The Amazon_S3_Sync log viewer.
   */
  public function __construct(Amazon_S3_SyncLogViewerInterface $amazon_s3_sync_log_viewer) {
    $this->Amazon_S3_SyncLogViewer = $amazon_s3_sync_log_viewer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('amazon_s3_sync.log_viewer')
    );
  }
}
