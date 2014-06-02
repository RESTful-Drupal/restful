<?php

$plugin = array(
  'label' => t('Request Token'),
  'resource' => 'request_tokens',
  'name' => 'request_tokens__1_0',
  'entity_type' => 'restful_request_token',
  'bundle' => 'restful_request_token',
  'description' => t('Generate a new request token.'),
  'class' => 'RestfulOAuthRequestTokenEndpoint',
  'cache_render' => FALSE,
  'hook_menu' => FALSE,
);
