<?php

$plugin = array(
  'label' => t('Refresh token authentication'),
  'resource' => 'refresh_token',
  'name' => 'refresh_token__1_0',
  'entity_type' => 'restful_token_auth',
  'bundle' => 'access_token',
  'description' => t('Export the refresh token authentication resource.'),
  'class' => 'RestfulRefreshTokenAuthentication',
  // We will implement hook_menu() with custom settings.
  'menu_item' => variable_get('restful_hook_menu_base_path', 'api') . '/refresh-token',
);
