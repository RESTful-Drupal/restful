<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_7',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with access checkers per endpoint.'),
  'class' => 'RestfulExampleArticlesResource__1_7',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'minor_version' => 7,
);
