<?php

$plugin = array(
  'label' => t('Entity Test'),
  'description' => t('Export the entity test with multiple bundle.'),
  'resource' => 'entity_tests',
  'name' => 'entity_tests__1_0',
  'entity_type' => 'entity_test',
  'bundles' => array(
    'main' => 'main',
    'test' => 'tests',
  ),
  'class' => 'RestfulTestEntityTestsResource',
);
