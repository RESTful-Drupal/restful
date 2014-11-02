<?php

$plugin = array(
  'label' => t('Access token authentication'),
  'resource' => 'access_token',
  'name' => 'access_token__1_0',
  'entity_type' => 'restful_token_auth',
  'bundle' => 'access_token',
  'description' => t('Export the access token authentication resource.'),
  'class' => 'RestfulAccessTokenAuthentication',
  'authentication_types' => array(
    'cookie',
    'basic_auth',
  ),
  // We will implement hook_menu() with custom settings.
  'menu_item' => variable_get('restful_hook_menu_base_path', 'api') . '/login-token',
);
