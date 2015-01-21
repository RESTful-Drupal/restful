<?php

$plugin = array(
  'label' => t('Watchdog entries'),
  'resource' => 'watchdog',
  'name' => 'watchdog',
  'data_provider_options' => array(
    'table_name' => 'watchdog',
    'id_column' => 'wid',
  ),
  'description' => t('Expose watchdog entries to the REST API.'),
  'class' => 'RestfulWatchdogResource',
  'authentication_types' => TRUE,
);
