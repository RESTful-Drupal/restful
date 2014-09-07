<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_6',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type in XML format.'),
  'class' => 'RestfulExampleArticlesResource__1_6',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'minor_version' => 6,
  // Output the data in XML following the common HAL conventions.
  'formatter' => 'hal_xml',
);
