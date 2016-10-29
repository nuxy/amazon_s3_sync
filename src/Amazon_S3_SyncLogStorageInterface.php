<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncLogStorage.
 */

namespace Drupal\amazon_s3_sync;

/**
 * Defines a common interface for Amazon_S3_SyncLog storage methods.
 */
interface Amazon_S3_SyncLogStorageInterface {

  /**
   * Remove all logs from the database.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function delete();
}
