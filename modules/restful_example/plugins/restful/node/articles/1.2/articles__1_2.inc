<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_2',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with "cookie" authentication.'),
  'class' => 'RestfulExampleArticlesResource',
  // Set the minor version.
  'minor_version' => 2,
  // Set the authentication to "cookie".
  'authentication_types' => array(
    'cookie',
  ),
);
