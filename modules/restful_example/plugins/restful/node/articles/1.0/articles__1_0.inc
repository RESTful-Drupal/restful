<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_0',
  'authentication types' => array(
    'cookie_auth',
  ),
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulExampleArticlesResource',
);
