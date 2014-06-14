<?php

$plugin = array(
  'label' => t('User'),
  'description' => t('Export the "User" entity.'),
  'resource' => 'test_users',
  'class' => 'RestfulEntityBaseUser',
  'entity_type' => 'user',
  'bundle' => 'user',
  'authentication_types' => array(
    'basic_auth',
  ),
);
