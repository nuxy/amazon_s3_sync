<?php

/**
 * @file
 * Contains \Drupal\amazon_s3_sync\Amazon_S3_SyncFileStorage.
 */

namespace Drupal\amazon_s3_sync;

use Drupal\Core\Database\Connection;

class Amazon_S3_SyncFileStorage implements Amazon_S3_SyncFileStorageInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Amazon_S3_SyncFileStorage object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    $result = $this->connection->delete('amazon_s3_sync_files')
      ->condition('id', $id)
      ->execute();
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($id) {
    $result = $this->connection->select('amazon_s3_sync_files', 'n')
      ->fields('n', array(''))
      ->condition('n.id', $id, '=')
      ->execute();
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $result = $this->connection->select('amazon_s3_sync_files', 'n')
      ->fields('n')
      ->execute();
    return $result->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
  }
}
