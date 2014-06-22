<?php

$plugin = array(
  'label' => t('File upload'),
  'description' => t('A file upload wrapped with RESTful.'),
  'resource' => 'files',
  'class' => 'RestfulFileUpload',
  'entity_type' => 'file',
  'authentication_types' => TRUE,
  // We will implement hook_menu() with custom settings.
  'hook_menu' => FALSE,
);
