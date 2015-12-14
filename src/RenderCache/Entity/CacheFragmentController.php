<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\Entity\CacheFragmentController.
 */

namespace Drupal\restful\RenderCache\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;

/**
 * Class CacheFragmentController.
 *
 * @package Drupal\restful\RenderCache\Entity
 */
class CacheFragmentController extends \EntityAPIController {

  /**
   * The type name of the cache fragment entity.
   */
  const ENTITY_TYPE = 'cache_fragment';

  /**
   * The name of the DB table holding the entities.
   *
   * @var string
   */
  protected static $tableName;

  /**
   * The name of the property that contains the ID of the entity.
   *
   * @var string
   */
  protected static $tableIdKey;

  /**
   * Creates all the caches tags from the tag collection.
   *
   * @param ArrayCollection $cache_fragments
   *   The collection of tags.
   *
   * @return CacheFragment[]
   *   An array of fragments.
   */
  public function createCacheFragments(ArrayCollection $cache_fragments) {
    $hash = $this->generateCacheHash($cache_fragments);
    if ($fragments = $this->existingFragments($hash)) {
      return $fragments;
    }
    foreach ($cache_fragments as $tag_type => $tag_value) {
      $cache_fragment = new CacheFragment(array(
        'value' => $tag_value,
        'type' => $tag_type,
        'hash' => $hash,
      ), static::ENTITY_TYPE);
      try {
        if ($id = $this->save($cache_fragment)) {
          $fragments[] = $cache_fragment;
        }
      }
      catch (\Exception $e) {
        // Log the exception. It's probably a duplicate fragment.
        watchdog_exception('restful', $e);
      }
    }
    return $fragments;
  }

  /**
   * Gets the existing fragments for a given hash.
   *
   * @param string $hash
   *   The hash.
   *
   * @return CacheFragment[]
   *   An array of fragments.
   */
  protected function existingFragments($hash) {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', static::ENTITY_TYPE)
      ->propertyCondition('hash', $hash)
      ->execute();
    return empty($results[static::ENTITY_TYPE]) ? array() : $this->load(array_keys($results[static::ENTITY_TYPE]));
  }

  /**
   * Generated the cache hash based on the cache fragments collection.
   *
   * @param ArrayCollection $cache_fragments
   *   The collection of tags.
   *
   * @return string
   *   The generated hash.
   */
  public function generateCacheHash(ArrayCollection $cache_fragments) {
    return substr(sha1(serialize($cache_fragments->toArray())), 0, 40);
  }

  /**
   * Gets the hashes for an EFQ.
   *
   * @param \EntityFieldQuery $query
   *   The EFQ.
   *
   * @return string[]
   *   The hashes that meet the conditions.
   */
  public static function lookUpHashes(\EntityFieldQuery $query) {
    $results = $query->execute();
    if (empty($results[static::ENTITY_TYPE])) {
      return array();
    }
    $fragment_ids = array_keys($results[static::ENTITY_TYPE]);

    $hashes = db_query('SELECT hash FROM {' . static::getTableName() . '} WHERE ' . static::getTableIdkey() . ' IN (:ids)', array(
      ':ids' => $fragment_ids,
    ))->fetchCol();
    return $hashes;
  }

  /**
   * Removes all the cache fragments.
   */
  public function wipe() {
    // We are not truncating the entity table so hooks are fired.
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', static::ENTITY_TYPE)
      ->execute();
    if (empty($results[static::ENTITY_TYPE])) {
      return;
    }
    if ($this->isFastDeleteEnabled()) {
      db_truncate($this::getTableName())->execute();
      return;
    }
    $this->delete(array_keys($results[static::ENTITY_TYPE]));
  }

  /**
   * {@inheritdoc}
   */
  public function delete($ids, \DatabaseTransaction $transaction = NULL) {
    if ($this->isFastDeleteEnabled()) {
      $this->fastDelete($ids, $transaction);
      return;
    }
    parent::delete($ids, $transaction);
  }

  /**
   * Do a fast delete without loading entities of firing delete hooks.
   *
   * @param array $ids
   *   An array of entity IDs.
   * @param \DatabaseTransaction $transaction
   *   Optionally a DatabaseTransaction object to use. Allows overrides to pass
   *   in their transaction object.
   *
   * @throws \Exception
   *   When there is a database error.
   */
  protected function fastDelete($ids, \DatabaseTransaction $transaction = NULL) {
    $transaction = isset($transaction) ? $transaction : db_transaction();

    try {
      db_delete($this::getTableName())
        ->condition($this::getTableIdkey(), $ids, 'IN')
        ->execute();

      // Reset the cache as soon as the changes have been applied.
      $this->resetCache($ids);

      // Ignore slave server temporarily.
      db_ignore_slave();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw $e;
    }
  }

  /**
   * Helper function that checks if this controller uses a fast delete.
   *
   * @return bool
   *   TRUE if fast delete is enabled. FALSE otherwise.
   */
  protected function isFastDeleteEnabled() {
    return (bool) variable_get('restful_fast_cache_clear', TRUE);
  }

  /**
   * Get the resource ID for the selected hash.
   *
   * @param string $hash
   *   The unique hash for the cache fragments.
   *
   * @return string
   *   The resource ID.
   */
  public static function resourceIdFromHash($hash) {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', static::ENTITY_TYPE)
      ->propertyCondition('type', 'resource')
      ->propertyCondition('hash', $hash)
      ->range(0, 1)
      ->execute();
    if (empty($results[static::ENTITY_TYPE])) {
      return NULL;
    }
    $cache_fragment = entity_load_single(static::ENTITY_TYPE, key($results[static::ENTITY_TYPE]));
    $pos = strpos($cache_fragment->value, CacheDecoratedResource::CACHE_PAIR_SEPARATOR);
    return $pos === FALSE ? $cache_fragment->value : substr($cache_fragment->value, 0, $pos);
  }

  /**
   * Gets the name of the table for the cache fragment entity.
   *
   * @return string
   *   The name.
   */
  protected static function getTableName() {
    if (static::$tableName) {
      return static::$tableName;
    }
    // Get the hashes from the base table.
    $info = entity_get_info(static::ENTITY_TYPE);
    static::$tableName = $info['base table'];
    return static::$tableName;
  }

  /**
   * Gets the name of the table for the cache fragment entity.
   *
   * @return string
   *   The name.
   */
  protected static function getTableIdkey() {
    if (static::$tableIdKey) {
      return static::$tableIdKey;
    }
    // Get the hashes from the base table.
    $info = entity_get_info(static::ENTITY_TYPE);
    static::$tableIdKey = $info['entity keys']['id'];
    return static::$tableIdKey;
  }

}
