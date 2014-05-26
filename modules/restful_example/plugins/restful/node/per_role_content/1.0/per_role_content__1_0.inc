<?php

$plugin = array(
  'label' => t('Content per role'),
  'resource' => 'per_role_content',
  'name' => 'per_role_content__1_0',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'options' => array(
    'roles' => array(
      'administrator',
    ),
  ),
  'bundle' => 'article',
  'description' => t('Get a list of all the nodes authored by users with the administration role.'),
  'class' => 'RestfulExampleRoleResource',
);
