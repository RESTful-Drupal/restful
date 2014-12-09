<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_7',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type in XML format.'),
  'class' => 'RestfulEntityBaseNode',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'minor_version' => 7,
  // Add the view mode information.
  'view_mode' => array(
    'name' => 'default',
    'mappings' => array(
      'body' => 'body',
      'field_tags' => 'tags',
      'field_image' => 'image',
    ),
  ),
);