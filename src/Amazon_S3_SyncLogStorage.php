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
  public function insert($command, $output, $start_time, $end_time) {

    // Strip S3 account information.
    $command = preg_replace('/--(?:access|secret)_key\s+[\w\+]+\s+/', '', $command);

    $result = $this->connection->insert('amazon_s3_sync_log')
      ->fields(
        array(
          'command' => $command,
          'output' => $output,
          'started' => $start_time,
          'ended' => $end_time,
        )
      )->execute();
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function purge() {
    $result = $this->connection->truncate('amazon_s3_sync_log')
      ->execute();
    return (bool) $result;
  }
}
