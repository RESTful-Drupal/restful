<?php

$plugin = array(
  'label' => t('HAL+JSON'),
  'description' => t('Output in using the HAL conventions and JSON format.'),
  'name' => 'hal_json',
  'class' => 'RestfulFormatterHalJson',
  'curie' => array(
    'name' => 'hal',
  ),
);


if (!drupal_is_cli()) {
  $plugin['curie']['href'] = url('docs/rels', array('absolute' => TRUE)) . '/{rel}';
}
