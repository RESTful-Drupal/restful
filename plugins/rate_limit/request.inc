<?php

$plugin = array(
  'label' => t('Any request'),
  'description' => t('The basic rate limit plugin. Every call to a resource is counted.'),
  'name' => 'request',
  'class' => 'RestfulRateLimitRequest',
);
