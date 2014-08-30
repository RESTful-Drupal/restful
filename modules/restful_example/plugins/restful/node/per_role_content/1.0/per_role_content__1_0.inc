<?php

$plugin = array(
  'label' => t('Content per role'),
  'resource' => 'per_role_content',
  'name' => 'per_role_content__1_0',
  // Authentication is optional, many resources are available to anonymous
  // users. Current authentication types are:
  //  - 'cookie': Uses regular Drupal session.
  //  - 'basic_auth': Uses basic auth credentials.
  //  - 'token': Use any of the methods above to get a valid token. Once you
  //    have a valid token use it to authenticate.
  'authentication_types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'options' => array(
    // This will be populated automatically in the 'get children' callback for
    // every child plugin.
    'roles' => array(),
  ),
  'bundle' => 'article',
  'description' => t('Get a list of all the nodes authored by users with the administration role.'),
  'class' => 'RestfulExampleRoleResource',
  // Callback function that will return modified instances of this plugin. Use
  // this as a way to provide a basic plugin in code and generate as many
  // plugins as you need. This is mostly used for plugins that store some of
  // their information in the database, such as views, blocks or exportable
  // custom versions of plugins.
  'get children' => 'restful_restful_per_role_content_get_children',
  // To implement, each plugin can have a 'get child' and 'get children'
  // callback. Both of these should be implemented for performance reasons,
  // since it is best to avoid getting all children if necessary.
  'get child' => 'restful_restful_per_role_content_get_child',
);

/**
 * Get children implementation.
 */
function restful_restful_per_role_content_get_children($plugin, $parent) {
  // In PHP 5.3 we can do something like this and defer the logic to a static
  // method. Sadly Drupal's autoloader won't deal with this too good.
  // forward_static_call_array($plugin['class'] . '::getChildren', func_get_args());

  $plugins = array();
  foreach (user_roles() as $role_name) {
    // Child plugins should be named parent:child, with the : being the
    // separator, so that it knows which parent plugin to ask for the child.
    $plugins[$parent . ':' . $role_name] = $plugin;
    $plugins[$parent . ':' . $role_name]['options'] = array(
      'roles' => array($role_name),
    );
    // Create endpoints like api/v1/administrator, api/v1/authenticated, etc'.
    $plugins[$parent . ':' . $role_name]['resource'] = str_replace(' ', '-', drupal_strtolower($role_name));
    // We need to add the name, otherwise the caching mechanism in
    // restful_get_restful_handler will return invalid data.
    $plugins[$parent . ':' . $role_name]['name'] = $parent . ':' . $role_name;
  }

  // Return the array of plugins available for this parent plugin. This is the
  // same concept as D8 plugin derivatives.
  return $plugins;
}

/**
 * Get children implementation.
 */
function restful_restful_per_role_content_get_child($plugin, $parent, $child) {
  // Avoid getting all the children when possible. This is to avoid unneeded
  // expensive operations. If this callback was not provided, then we would call
  // the 'get children' callback and return the plugin for $child.
  $plugin['options'] = array(
    'roles' => array($child),
  );
  return $plugin;
}
