<?php

$plugin = array(
  'label' => t('Variable'),
  'description' => t('Expose site variables to the REST API.'),
  'resource' => 'variables',
  'class' => 'RestfulVariableResource',
  'authentication_types' => TRUE,
  'render_cache' => array(
    'render' => TRUE,
  ),
);
