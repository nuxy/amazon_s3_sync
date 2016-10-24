<?php

/**
 * @file
 * Contains \Drupal\newsletter\Amazon_S3_SyncFileStorage.
 */

namespace Drupal\amazon_s3_sync;

/**
 * Defines a common interface for Amazon_S3_SyncFile storage methods.
 */
interface Amazon_S3_SyncFileStorageInterface {

  /**
   * Remove a file from the database.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function delete($id);

  /**
   * Check if a file exists.
   *
   * @return bool
   *   TRUE if exists, FALSE if not.
   */
  public function exists($id);

  /**
   * Get all files.
   *
   * @return array
   *   Array of file data.
   */
  public function getAll();
}
