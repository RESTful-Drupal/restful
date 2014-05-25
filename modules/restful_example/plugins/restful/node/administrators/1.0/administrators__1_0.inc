<?php

$plugin = array(
  'label' => t('Administrator'),
  'resource' => 'administrators',
  'name' => 'administrators__1_0',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'options' => array(
    'roles' => array('administrator'),
  ),
  'bundles' => array(
    'article',
    'page',
  ),
  'description' => t('Get a list of all the nodes authored by users with the administration role.'),
  'class' => 'RestfulExampleRoleResource',
);
