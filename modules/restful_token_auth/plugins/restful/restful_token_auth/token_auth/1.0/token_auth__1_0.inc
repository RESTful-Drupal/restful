<?php

$plugin = array(
  'label' => t('Token authentication'),
  'resource' => 'token_auth',
  'name' => 'token_auth__1_0',
  'entity_type' => 'restful_token_auth',
  'bundle' => 'restful_token_auth',
  'description' => t('Export the token authentication resource.'),
  'class' => 'RestfulTokenAuth',
  'authentication types' => array(
    'base_auth',
  ),
);
