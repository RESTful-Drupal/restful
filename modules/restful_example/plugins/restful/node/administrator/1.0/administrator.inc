<?php

$plugin = array(
  'label' => t('Administrator'),
  'resource' => 'administrator',
  'name' => 'administrator',
  'authentication types' => array(
    'cookie',
  ),
  'entity_type' => 'node',
  'options' => array(
    'roles' => array('administrator'),
  ),
  'description' => t('Get a list of all the nodes authored by users with the administration role.'),
  'class' => 'RestfulExampleRoleResource',
);
