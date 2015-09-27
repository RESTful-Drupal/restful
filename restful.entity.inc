<?php

/**
 * @file
 * Contains entity related code.
 */

use Drupal\restful\RenderCache\Entity\CacheFragmentController;
use Drupal\restful\RenderCache\RenderCache;

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
    'entity cache' => module_exists('entitycache'),
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
  $query = new \EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'cache_fragment')
    ->propertyCondition('type', 'entity_type')
    ->propertyCondition('value', $type);
  if (!$hashes = CacheFragmentController::lookUpHashes($query)) {
    return array();
  }

  // Search for all the cache fragments with our entity id.
  list($entity_id,,) = entity_extract_ids($type, $entity);
  $query = new \EntityFieldQuery();
  $query
    ->entityCondition('entity_type', 'cache_fragment')
    ->propertyCondition('type', 'entity_id')
    ->propertyCondition('value', $entity_id)
    ->propertyCondition('hash', $hashes, 'IN');
  return CacheFragmentController::lookUpHashes($query);
}

/**
 * Implements hook_entity_update().
 */
function restful_entity_update($entity, $type) {
  foreach (_restful_entity_cache_hashes($entity, $type) as $hash) {
    cache_clear_all($hash, RenderCache::CACHE_BIN);
  }
}

/**
 * Implements hook_entity_delete().
 */
function restful_entity_delete($entity, $type) {
  foreach (_restful_entity_cache_hashes($entity, $type) as $hash) {
    cache_clear_all($hash, RenderCache::CACHE_BIN);
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
  return CacheFragmentController::lookUpHashes($query);
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
  return CacheFragmentController::lookUpHashes($query);
}
