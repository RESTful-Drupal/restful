<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'test_articles',
  'name' => 'test_articles__1_1',
  'authentication_types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_1',
  'render_cache' => array(
    'render' => TRUE,
  ),
  'minor_version' => 1,
);
