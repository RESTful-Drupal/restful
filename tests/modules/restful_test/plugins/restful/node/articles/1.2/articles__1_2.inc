<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_2',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_2',
  // Set the minor version.
  'minor_version' => 2,
);
