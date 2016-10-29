<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncLogStorage.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Database\Connection;

class Amazon_S3_SyncLogStorage implements Amazon_S3_SyncLogStorageInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Amazon_S3_SyncLogStorage object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    $result = $this->connection->truncate('amazon_s3_sync_log')
      ->execute();
    return (bool) $result;
  }
}
