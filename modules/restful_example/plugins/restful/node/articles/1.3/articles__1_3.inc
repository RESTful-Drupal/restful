<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_3',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with "token" authentication.'),
  'class' => 'RestfulExampleArticlesResource',
  // Set the minor version.
  'minor_version' => 3,
  // Set the authentication to token.
  // @todo: Depends on "RESTful auth token" module, so move to that module.
  // Also change the test's resources not to be articles.
  'authentication types' => array(
    'token',
  ),
);
