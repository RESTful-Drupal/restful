<?php

$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_4',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type with rate limit for anonymous users.'),
  'class' => 'RestfulExampleArticlesResource',
  // Set the minor version.
  'minor_version' => 4,
  // Set the authentication to token.
  'authentication_types' => array(
    'cookie',
    'basic_auth',
  ),
  'rate_limit' => array(
    // The 'request' event is the basic event. You can declare your own events.
    'request' => array(
      'event' => 'request',
      // Rate limit is cleared every day.
      'period' => new \DateInterval('P1D'),
      'limits' => array(
        'authenticated user' => 5,
        'anonymous user' => 2,
        'administrator' => \RestfulRateLimitManager::INFINITE_RATE_LIMIT,
      ),
    ),
  ),
);
