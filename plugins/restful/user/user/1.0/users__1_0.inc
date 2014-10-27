<?php

// By checking the variable, we allow implementing modules to easily implement
// their own "users" resource.
if (variable_get('restful_enable_users_resource', TRUE)) {

  $plugin = array(
    'label' => t('User'),
    'description' => t('Export the "User" entity.'),
    'resource' => 'users',
    'class' => 'RestfulEntityUser__1_0',
    'entity_type' => 'user',
    'bundle' => 'user',
    // Try to authenticate users with all available authentication types.
    'authentication_types' => TRUE,
    // Allow anonymous users to access the resource, given they have the right
    // permissions.
    'authentication_optional' => TRUE,
  );
}
