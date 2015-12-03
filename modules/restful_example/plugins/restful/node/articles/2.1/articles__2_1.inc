<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__2_1',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Example for a plugin with altered public fields.'),
  'class' => 'RestfulExampleArticlesResource__1_0',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'major_version' => 1,
  'minor_version' => 0,
  'allow_public_alter' => FALSE,
);
