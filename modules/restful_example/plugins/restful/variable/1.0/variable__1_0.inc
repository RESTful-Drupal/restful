<?php

$plugin = array(
  'label' => t('Variable'),
  'description' => t('Export the site variables.'),
  'resource' => 'variables',
  'class' => 'RestfulVariableResource',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
