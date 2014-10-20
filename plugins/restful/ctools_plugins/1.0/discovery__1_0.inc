<?php

$plugin = array(
  'label' => t('Discovery'),
  'description' => t('Discovery plugin'),
  'resource' => 'discovery',
  'class' => 'RestfulCToolsPluginsDiscovery',
  'data_provider_options' => array(
    'module' => 'restful',
    'type' => 'restful',
  ),
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
