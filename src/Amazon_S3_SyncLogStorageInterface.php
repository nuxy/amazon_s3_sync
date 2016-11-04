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
   * Insert a log entry into the database. Excludes S3 account information.
   *
   * @param string $operation
   *   The S3cmd operation that was executed.
   * @param string $output
   *   The operation result (verbose must be enabled).
   * @param string $start_time
   *   The operation execution start time.
   * @param string $end_time
   *   The operation execution end time.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function insert($operation, $output, $start_time, $end_time);

  /**
   * Remove all logs from the database.
   *
   * @return bool
   *   TRUE if success, FALSE if not.
   */
  public function purge();
}
