<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_SyncS3cmdInterface.
 */

namespace Drupal\amazon_s3_sync;

/**
 * Provides an interface defining a Amazon_S3_Sync commands.
 */
interface Amazon_S3_SyncS3cmdInterface {

  /**
   * Synchronize Drupal files with the Amazon S3 bucket(s).
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function sync();
}
