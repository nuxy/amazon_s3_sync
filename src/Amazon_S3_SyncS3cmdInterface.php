<?php

/**
 * @file
 * Contains \Drupal\Amazon_S3_SyncS3cmdInterface.
 */

namespace Drupal\amazon_s3_sync;

/**
 * Provides an interface defining a Amazon_S3_Sync operations.
 */
interface Amazon_S3_SyncS3cmdInterface {

  /**
   * Empty the Amazon S3 bucket.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  // @codingStandardsIgnoreLine
  public function empty($region_code);

  /**
   * Synchronize Drupal files with the Amazon S3 bucket(s).
   *
   * @param string $source
   *   Absolute path to the local file or directory.
   * @param string $target
   *   Relative path to $source to upload to the S3 (optional).
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function sync($source, $target = NULL);

  /**
   * Define a new S3cmd option argument.
   *
   * @param string $key
   *   The command-line option prefix with --
   * @param string $value
   *   The command-line option value. May contain a single value or array of values.
   *
   * @return \Drupal\Amazon_S3_SyncS3cmd
   */
  public function setOption($key, $value = NULL);

  /**
   * Define a new S3cmd parameter argument.
   *
   * @param string $value
   *   The command-line parameter value. Must be a path to a source or S3 bucket target.
   *
   * @return \Drupal\Amazon_S3_SyncS3cmd
   */
  public function setParameter($value);
}
