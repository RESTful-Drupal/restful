<?php

/**
 * @file
 * Contains entity related code.
 */

use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResourceInterface;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;
use Drupal\restful\RenderCache\RenderCache;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
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
  $resource_manager = restful()->getResourceManager();
  foreach (_restful_entity_cache_hashes($entity, $type) as $hash) {
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
    // Do a clear with the RenderCache object to also remove the cache fragment
    // entities.
    $cache_object->clear();
  }
}

/**
 * Implements hook_entity_delete().
 */
function restful_entity_delete($entity, $type) {
  $resource_manager = restful()->getResourceManager();
  foreach (_restful_entity_cache_hashes($entity, $type) as $hash) {
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
    // Do a clear with the RenderCache object to also remove the cache fragment
    // entities.
    $cache_object->clear();
  }
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
  $resource_manager = restful()->getResourceManager();
  foreach (CacheFragmentController::lookUpHashes($query) as $hash) {
    if (!$plugin_id = CacheFragmentController::resourceIdFromHash($hash)) {
      continue;
    }
    try {
      $handler = $resource_manager->getPlugin($plugin_id);
    }
    catch (PluginNotFoundException $e) {
      continue;
    }
    if (!$handler instanceof CacheDecoratedResourceInterface) {
      return;
    }
    if (!$handler->hasSimpleInvalidation()) {
      return;
    }
    // You can get away without the fragments for a clear.
    $cache_object = new RenderCache(new ArrayCollection(), $hash, $handler->getCacheController());
    // Do a clear with the RenderCache object to also remove the cache fragment
    // entities.
    $cache_object->clear();
  }
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
  $resource_manager = restful()->getResourceManager();
  foreach (CacheFragmentController::lookUpHashes($query) as $hash) {
    $handler = $resource_manager->getPlugin(CacheFragmentController::resourceIdFromHash($hash));
    if (!$handler instanceof CacheDecoratedResourceInterface) {
      return;
    }
    if (!$handler->hasSimpleInvalidation()) {
      return;
    }
    // You can get away without the fragments for a clear.
    $cache_object = new RenderCache(NULL, $hash, $handler->getCacheController());
    // Do a clear with the RenderCache object to also remove the cache fragment
    // entities.
    $cache_object->clear();
  }
}
