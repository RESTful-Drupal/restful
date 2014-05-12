<?php

$plugin = array(
  'label' => t('Tests'),
  'description' => t('Export the entity test "test" bundle.'),
  'resource' => 'tests',
  'name' => 'tests__1_0',
  'entity_type' => 'entity_test',
  'bundle' => 'test',
  'class' => 'RestfulTestTestsResource',
);
