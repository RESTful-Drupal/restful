<?php

/**
 * @file
 * Contains entity related code.
 */

use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResourceInterface;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;
use Drupal\restful\RenderCache\RenderCache;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Implements hook_entity_info().
 */
function restful_entity_info() {
  $items['rate_limit'] = array(
    'label' => t('Rate limit'),
    'entity class' => '\\Drupal\\restful\\RateLimit\\Entity\\RateLimit',
    'controller class' => '\\Drupal\\restful\\RateLimit\\Entity\\RateLimitController',
    'base table' => 'restful_rate_limit',
    'fieldable' => TRUE,
    'entity keys' => array(
      'id' => 'rlid',
      'label' => 'identifier',
      'bundle' => 'event',
    ),
    'bundles' => array(),
    'bundle keys' => array(
      'bundle' => 'type',
    ),
    'module' => 'restful',
    'entity cache' => module_exists('entitycache'),
  );
  $items['cache_fragment'] = array(
    'label' => t('Cache fragment'),
    'entity class' => '\\Drupal\\restful\\RenderCache\\Entity\\CacheFragment',
    'controller class' => '\\Drupal\\restful\\RenderCache\\Entity\\CacheFragmentController',
    'base table' => 'restful_cache_fragment',
    'fieldable' => FALSE,
    'entity keys' => array(
      'id' => 'tid',
      'label' => 'identifier',
      'bundle' => 'type',
    ),
    'bundles' => array(),
    'bundle keys' => array(
      'bundle' => 'type',
    ),
    'module' => 'restful',
    'entity cache' => FALSE,
  );

  return $items;
}

/**
 * Helper function that extract cache hashes from an entity.
 */
function _restful_entity_cache_hashes($entity, $type) {
  if ($type == 'cache_fragment') {
    return array();
  }
  // Limit to the fragments for our entity type.
  list($entity_id) = entity_extract_ids($type, $entity);
  $query = new \EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'cache_fragment')
    ->propertyCondition('type', 'entity')
    ->propertyCondition('value', CacheDecoratedResource::serializeKeyValue($type, $entity_id));
  return CacheFragmentController::lookUpHashes($query);
}

/**
 * Implements hook_entity_update().
 */
function restful_entity_update($entity, $type) {
  $hashes = &drupal_static('restful_entity_clear_hashes', array());
  $new_hashes = _restful_entity_cache_hashes($entity, $type);
  array_walk($new_hashes, '_restful_entity_clear_all_resources');
  $hashes += $new_hashes;
  restful_register_shutdown_function_once('restful_entity_clear_render_cache');
}

/**
 * Implements hook_entity_delete().
 */
function restful_entity_delete($entity, $type) {
  $hashes = &drupal_static('restful_entity_clear_hashes', array());
  $new_hashes = _restful_entity_cache_hashes($entity, $type);
  array_walk($new_hashes, '_restful_entity_clear_all_resources');
  $hashes += $new_hashes;
  restful_register_shutdown_function_once('restful_entity_clear_render_cache');
}

/**
 * Implements hook_user_update().
 */
function restful_user_update(&$edit, $account, $category) {
  // Search for all the cache fragments with our entity id.
  $query = new \EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'cache_fragment')
    ->propertyCondition('type', 'user_id')
    ->propertyCondition('value', $account->uid);
  $hashes = &drupal_static('restful_entity_clear_hashes', array());
  $new_hashes = CacheFragmentController::lookUpHashes($query);
  array_walk($new_hashes, '_restful_entity_clear_all_resources');
  $hashes += $new_hashes;
  restful_register_shutdown_function_once('restful_entity_clear_render_cache');
}

/**
 * Implements hook_user_delete().
 */
function restful_user_delete($account) {
  // Search for all the cache fragments with our entity id.
  $query = new \EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'cache_fragment')
    ->propertyCondition('type', 'user_id')
    ->propertyCondition('value', $account->uid);
  $hashes = &drupal_static('restful_entity_clear_hashes', array());
  $new_hashes = CacheFragmentController::lookUpHashes($query);
  array_walk($new_hashes, '_restful_entity_clear_all_resources');
  $hashes += $new_hashes;
  restful_register_shutdown_function_once('restful_entity_clear_render_cache');
}

/**
 * Helper function to schedule a shutdown once.
 *
 * @param callable $callback
 *   The callback.
 */
function restful_register_shutdown_function_once($callback) {
  $existing_callbacks = drupal_register_shutdown_function();
  $added = (bool) array_filter($existing_callbacks, function ($item) use ($callback) {
    return $item['callback'] == $callback;
  });
  if (!$added) {
    drupal_register_shutdown_function($callback);
  }
}

/**
 * Clear the cache back ends for the given hash.
 *
 * @param string $cid
 *   The cache ID to clear.
 */
function _restful_entity_clear_all_resources($cid) {
  if (!$instance_id = CacheFragmentController::resourceIdFromHash($cid)) {
    return;
  }
  $handler = restful()
    ->getResourceManager()
    ->getPlugin($instance_id);
  if (!$handler instanceof CacheDecoratedResourceInterface) {
    return;
  }
  // Clear the cache bin.
  $handler->getCacheController()->clear($cid);
}

/**
 * Delete the scheduled fragments and caches on shutdown.
 */
function restful_entity_clear_render_cache() {
  if ($hashes = drupal_static('restful_entity_clear_hashes', array())) {
    $hashes = array_unique($hashes);
    drupal_static_reset('restful_entity_clear_hashes');
    $resource_manager = restful()->getResourceManager();
    foreach ($hashes as $hash) {
      if (!$instance_id = CacheFragmentController::resourceIdFromHash($hash)) {
        continue;
      }
      $handler = $resource_manager->getPlugin($instance_id);
      if (!$handler instanceof CacheDecoratedResourceInterface) {
        continue;
      }
      if (!$handler->hasSimpleInvalidation()) {
        continue;
      }
      // You can get away without the fragments for a clear.
      $cache_object = new RenderCache(new ArrayCollection(), $hash, $handler->getCacheController());
      // Do a clear with the RenderCache object to also remove the cache
      // fragment entities.
      $cache_object->clear();
    }
  }
}
