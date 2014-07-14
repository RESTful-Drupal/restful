<?php

if (variable_get('restful_file_upload', FALSE)) {
  $plugin = array(
    'label' => t('File upload'),
    'description' => t('A file upload wrapped with RESTful.'),
    'resource' => 'files',
    'class' => 'RestfulFilesUpload',
    'entity_type' => 'file',
    'authentication_types' => TRUE,
    'authentication_optional' => variable_get('restful_file_upload_allow_anonymous_user', FALSE),
    // We will implement hook_menu() with custom settings.
    'hook_menu' => FALSE,
  );
}
