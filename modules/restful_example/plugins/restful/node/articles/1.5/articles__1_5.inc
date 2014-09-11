<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_5',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with "cookie" authentication.'),
  'class' => 'RestfulExampleArticlesResource__1_5',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'minor_version' => 5,
  'options' => array(
    'image_styles' => array(
      'image' => array('thumbnail', 'medium', 'large'),
    ),
  ),
);
