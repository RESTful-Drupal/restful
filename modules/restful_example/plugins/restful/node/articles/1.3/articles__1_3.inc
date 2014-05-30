<?php

if (module_exists('restful_token_auth')) {

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
  'authentication_types' => array(
    'token',
  ),
);

}
