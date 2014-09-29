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
  'authentication_optional' => TRUE,
  // We will decalre our own custom menu item.
  'hook_menu' => FALSE,
);
