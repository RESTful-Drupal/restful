<?php

$plugin = array(
  'label' => t('User'),
  'description' => t('Export the "User" entity.'),
  'resource' => 'users',
  'class' => 'RestfulEntityBaseUser',
  'entity_type' => 'user',
  'bundle' => 'user',
  // Try to authenticate users with all available authentication types.
  'authentication_types' => TRUE,
  // Allow anonymous user to access the resource, given they have the right
  // permissions.
  'authentication_optional' => TRUE,
);
