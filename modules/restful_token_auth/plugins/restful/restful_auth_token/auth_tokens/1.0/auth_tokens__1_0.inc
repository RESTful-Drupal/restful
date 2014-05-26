<?php

$plugin = array(
  'label' => t('Authentication tokens'),
  'resource' => 'auth_tokens',
  'name' => 'auth_tokens__1_0',
  'entity_type' => 'restful_token_auth',
  'bundle' => 'restful_token_auth',
  'description' => t('Export the authentication token.'),
  'class' => 'RestfulAuthTokens',
  'authentication types' => array(
    'cookie',
  ),
);
