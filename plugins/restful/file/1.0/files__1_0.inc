<?php

$plugin = array(
  'label' => t('File upload'),
  'description' => t('A file upload wrapped with RESTful.'),
  'resource' => 'files',
  'class' => 'RestfulFileUpload',
  'entity_type' => 'file',
);
