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
      ), 'cache_fragment');
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
      ->entityCondition('entity_type', 'cache_fragment')
      ->propertyCondition('hash', $hash)
      ->execute();
    return empty($results['cache_fragment']) ? array() : $this->load(array_keys($results['cache_fragment']));
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
    if (empty($results['cache_fragment'])) {
      return array();
    }
    $fragment_ids = array_keys($results['cache_fragment']);

    // Get the hashes from the base table.
    $info = entity_get_info('cache_fragment');
    $entity_table = $info['base table'];
    $entity_id_key = $info['entity keys']['id'];
    $hashes = db_query('SELECT hash FROM {' . $entity_table . '} WHERE ' . $entity_id_key . ' IN (:ids)', array(
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
      ->entityCondition('entity_type', 'cache_fragment')
      ->execute();
    if (empty($results['cache_fragment'])) {
      return;
    }
    if ($this->isFastDeleteEnabled()) {
      db_truncate('cache_fragment');
      return;
    }
    $this->delete(array_keys($results['cache_fragment']));
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
      db_delete($this->entityInfo['base table'])
        ->condition($this->idKey, $ids, 'IN')
        ->execute();

      if (isset($this->revisionTable)) {
        db_delete($this->revisionTable)
          ->condition($this->idKey, $ids, 'IN')
          ->execute();
      }
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
      ->entityCondition('entity_type', 'cache_fragment')
      ->propertyCondition('type', 'resource')
      ->propertyCondition('hash', $hash)
      ->range(0, 1)
      ->execute();
    if (empty($results['cache_fragment'])) {
      return NULL;
    }
    $cache_fragment = entity_load_single('cache_fragment', key($results['cache_fragment']));
    $pos = strpos($cache_fragment->value, CacheDecoratedResource::CACHE_PAIR_SEPARATOR);
    return $pos === FALSE ? $cache_fragment->value : substr($cache_fragment->value, 0, $pos);
  }

}
