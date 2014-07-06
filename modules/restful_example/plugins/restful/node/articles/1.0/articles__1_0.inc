<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_0',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with "cookie" authentication.'),
  'class' => 'RestfulExampleArticlesResource',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
