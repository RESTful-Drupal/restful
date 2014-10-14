<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'test_articles',
  'name' => 'test_articles__1_4',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_4',
  'minor_version' => 4,
  'allow_origin' => '*',
  'formatter' => 'hal_xml',
);
