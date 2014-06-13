<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'test_articles',
  'name' => 'test_articles__1_0',
  'authentication_types' => array(
    'basic_auth',
    'cookie',
  ),
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_0',
);
