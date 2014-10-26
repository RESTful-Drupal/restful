<?php

$plugin = array(
  'label' => t('CSRF token'),
  'resource' => 'csrf_token',
  'name' => 'csrf_token',
  // Resource is not an entity type.
  'entity_type' => FALSE,
  'description' => t('Export the CSRF token unique for each user.'),
  'class' => 'RestfulCsrfTokenResource',
  'authentication_types' => TRUE,
  // Csrf token should be exposed also to anonymous users.
  'authentication_optional' => TRUE,
  // We will decalre our own custom menu item.
  'menu_item' => variable_get('restful_hook_menu_base_path', 'api') . '/session/token',
);
