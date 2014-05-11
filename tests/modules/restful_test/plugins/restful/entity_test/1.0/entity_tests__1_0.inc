<?php

$plugin = array(
  'label' => t('Entity Test'),
  'description' => t('Export the entity test "main" bundle.'),
  'resource' => 'entity_tests',
  'name' => 'entity_tests__1_0',
  'entity_type' => 'entity_test',
  'bundle' => 'main',
  'class' => 'RestfulTestEntityTestsResource',
);
