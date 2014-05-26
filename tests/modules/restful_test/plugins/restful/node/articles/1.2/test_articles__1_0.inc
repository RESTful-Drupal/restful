<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'test_articles',
  'name' => 'test_articles__1_2',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_0',
);
