<?php

$plugin = array(
  'label' => t('Main'),
  'description' => t('Export the entity test "main" bundle.'),
  'resource' => 'main',
  'name' => 'main__1_5',
  'entity_type' => 'entity_test',
  'bundle' => 'main',
  'authentication types' => array(
    'cookie',
  ),
  'class' => 'RestfulTestMainResource__1_5',
  'minor_version' => 5,
);
