<?php

$plugin = array(
  'label' => t('Variable'),
  'description' => t('Export the "Variable" table.'),
  'resource' => 'variables',
  'class' => 'RestfulQueryVariable',
  'data_provider_options' => array(
    'table_name' => 'variable',
    'id_column' => 'name',
  ),
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
