<?php

$plugin = array(
  'label' => t('Content per role'),
  'resource' => 'per_role_content',
  'name' => 'per_role_content__1_0',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'options' => array(
    'roles' => array(),
  ),
  'bundle' => 'article',
  'description' => t('Get a list of all the nodes authored by users with the administration role.'),
  'class' => 'RestfulExampleRoleResource',
  'get children' => 'restful_restful_per_role_content_get_children',
  'get child' => 'restful_restful_per_role_content_get_child',
);

/**
 * Get children implementation.
 */
function restful_restful_per_role_content_get_children($plugin, $parent) {
  $plugins = array();
  foreach (user_roles() as $rid => $role_name) {
    $plugins[$parent . ':' . $role_name] = $plugin;
    $plugins[$parent . ':' . $role_name]['options'] = array(
      'roles' => array($role_name),
    );
    // Create endpoints like api/v1/administrator, api/v1/authenticated-user ...
    $plugins[$parent . ':' . $role_name]['resource'] = preg_replace('/\s+/', '-', drupal_strtolower($role_name));
  }
  return $plugins;
}

/**
 * Get children implementation.
 */
function restful_restful_per_role_content_get_child($plugin, $parent, $child) {
  $plugin['options'] = array(
    'roles' => array($child),
  );
  return $plugin;
}
