<?php

$plugin = array(
  'label' => t('Variable'),
  'description' => t('Export the "Variable" table.'),
  'resource' => 'variables',
  'class' => 'RestfulQueryVariable',
  'table_name' => 'variable',
  'id_column' => 'name',
  // Try to authenticate users with all available authentication types.
  'authentication_types' => TRUE,
  // Allow anonymous users to access the resource, given they have the right
  // permissions.
  'authentication_optional' => TRUE,
);
