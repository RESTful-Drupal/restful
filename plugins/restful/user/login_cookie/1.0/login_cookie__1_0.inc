<?php

$plugin = array(
  'label' => t('Login'),
  'description' => t('Login a user and return a JSON along with the authentication cookie..'),
  'resource' => 'login_cookie',
  'class' => 'RestfulUserLoginCookie',
  'entity_type' => 'user',
  'bundle' => 'user',
  'authentication_types' => array(
    'basic_auth',
  ),
  // We will implement hook_menu() with custom settings.
  'hook_menu' => FALSE,
);
