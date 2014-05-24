<?php

$plugin = array(
  'label' => t('Entity Test'),
  'description' => t('Export the entity test with multiple bundle.'),
  'resource' => 'entity_tests',
  'name' => 'entity_tests__1_1',
  'entity_type' => 'entity_test',
  'bundle' => 'main',
  'authentication types' => array(
    'cookie',
  ),
  'class' => 'RestfulTestEntityTestsResource__1_1',
  'minor_version' => 1,
);
