<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_1',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulTestArticlesResource__1_1',
  // Set the minor version.
  'restful_minor_version' => 1,
);
