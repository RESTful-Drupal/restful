<?php

/**
 * @file
 * Contains entity related code.
 */

/**
 * Implements hook_entity_info().
 */
function restful_entity_info() {
  $items['rate_limit'] = array(
    'label' => t('Rate limit'),
    'entity class' => 'RestfulRateLimit',
    'controller class' => 'EntityAPIController',
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

  return $items;
}

/**
 * Implements hook_entity_update().
 */
function restful_entity_update($entity, $type) {
  if (restful_entity_determine_invalidation_by_type($type)) {
    list($entity_id) = entity_extract_ids($type, $entity);
    $cid = 'paet:' . $type . '::ei:' . $entity_id;
    \RestfulManager::invalidateEntityCache($cid);
  }
}

/**
 * Implements hook_entity_delete().
 */
function restful_entity_delete($entity, $type) {
  if (restful_entity_determine_invalidation_by_type($type)) {
    list($entity_id) = entity_extract_ids($type, $entity);
    $cid = 'paet:' . $type . '::ei:' . $entity_id;
    \RestfulManager::invalidateEntityCache($cid);
  }
}

/**
 * Helper function, used in the entity hooks.
 * 
 * To determine whether entities are supposed to be cached or not.
 * 
 * @param string $entity_type
 *   Entity type to be tested.
 * @return bool 
 */

function restful_entity_determine_invalidation_by_type($entity_type) {
  $skip_types = array(
    // Saves to the configuration entity don't necessarily need to be
    // cached & invalidated.   
    'rate_limit',
    // User is being dealt with separately.
    'user'
  );
  $skip_types = array_merge(
    $skip_types, 
    variable_get('restful_entities_to_skip', array())
  );
  return !in_array($entity_type, $skip_types);
}

/**
 * Implements hook_user_update().
 */
function restful_user_update(&$edit, $account, $category) {
  // Due to the limitations for cid matching on clearing caches, we need to
  // clear all the bin. We cannot do $cid = '%::uu' . $account->uid . '::pa';
  \RestfulManager::invalidateEntityCache('*');
}

/**
 * Implements hook_user_delete().
 */
function restful_user_delete($account) {
  // Due to the limitations for cid matching on clearing caches, we need to
  // clear all the bin. We cannot do $cid = '%::uu' . $account->uid . '::pa';
  \RestfulManager::invalidateEntityCache('*');
}
