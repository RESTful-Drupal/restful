<?php

$plugin = array(
  'label' => t('Get String authentication'),
  'description' => t('Authenticate requests based on a secret get parameter.'),
  'name' => 'get_string',
  'authentication' => FALSE,
  'settings' => array(
    'parameter name method' => 'getParamName',
    'parameter value method' => 'getParamValue',
  ),
  'class' => 'RestfulAuthenticationGetString',
);
