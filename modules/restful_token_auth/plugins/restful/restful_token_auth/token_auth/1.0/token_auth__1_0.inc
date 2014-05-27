<?php

$plugin = array(
  'label' => t('Token authentication'),
  'resource' => 'token_auth',
  'name' => 'token_auth__1_0',
  'entity_type' => 'restful_token_auth',
  'bundle' => 'restful_token_auth',
  'description' => t('Export the token authentication resource.'),
  'class' => 'RestfulTokenAuthentication',
  'authentication types' => array(
    'cookie',
    'basic_auth',
  ),
  'hook_menu' => variable_get('restful_token_auth_plugin_menu_item', 'api/login'),
);
