<?php

$plugin = array(
  'label' => t('Discovery'),
  'resource' => 'discovery',
  'name' => 'discovery',
  // Resource is not an entity type.
  'entity_type' => FALSE,
  'description' => t('Export the plugin definitions.'),
  'class' => 'RestfulDiscoveryResource',
  'authentication_types' => TRUE,
  // Csrf token should be exposed also to anonymous users.
  'authentication_optional' => TRUE,
  // We will declare our own custom menu item.
  'hook_menu' => FALSE,
);
