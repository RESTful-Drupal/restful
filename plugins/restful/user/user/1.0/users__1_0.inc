<?php

$plugin = array(
  'label' => t('User'),
  'description' => t('Export the "User" entity.'),
  'resource' => 'users',
  'class' => 'RestfulEntityBaseUser',
  'entity_type' => 'user',
  'bundle' => 'user',
);
