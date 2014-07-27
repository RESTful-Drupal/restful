<?php

$plugin = array(
  'label' => t('Tags'),
  'resource' => 'test_tags',
  'name' => 'test_tags__1_0',
  'entity_type' => 'taxonomy_term',
  'bundle' => 'tags',
  'description' => t('Export the "tags" taxonomy term.'),
  'class' => 'RestfulEntityTaxonomyTermTestTags',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
