<?php

$plugin = array(
  'label' => t('OAuth Consumer'),
  'resource' => 'oauth_consumers',
  'name' => 'oauth_consumers__1_0',
  'entity_type' => 'restful_oauth_consumer',
  'bundle' => 'restful_oauth_consumer',
  'description' => t('Register a new consumer.'),
  'class' => 'RestfulOAuthConsumerEndpoint',
  'authentication types' => array(
    'cookie',
    'basic_auth',
  ),
  'cache_render' => FALSE,
);
