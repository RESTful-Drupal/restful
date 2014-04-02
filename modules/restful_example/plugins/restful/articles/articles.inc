<?php

$plugin = array(
  'label' => t('Articles'),
  'name' => 'articles',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulExampleArticlesResource',
);
