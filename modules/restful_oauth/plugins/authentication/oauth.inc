<?php

$plugin = array(
  'label' => t('OAuth based authentication'),
  'description' => t('Authenticate requests based on OAuth.'),
  'name' => 'oauth',
  'class' => 'RestfulAuthenticationOAuth',
  'options' => array(
    'param_name' => 'access_token',
  )
);
