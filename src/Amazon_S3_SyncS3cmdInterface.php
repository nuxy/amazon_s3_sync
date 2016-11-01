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
   * Empty the Amazon S3 bucket(s).
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function empty();

  /**
   * Synchronize Drupal files with the Amazon S3 bucket(s).
   *
   * @param string $path
   *   Absolute path to the local $source file.
   * @param string $source
   *   Filename or path to upload to the S3.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function sync($path, $source);
}
