<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__2_0',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with "cookie" authentication.'),
  'class' => 'RestfulExampleArticlesResource__2_0',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'major_version' => 2,
  'minor_version' => 0,
);
