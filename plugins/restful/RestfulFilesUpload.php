<?php

/**
 * @file
 * Contains RestfulFilesUpload.
 */

class RestfulFilesUpload extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::controllers.
   */
  protected $controllers = array(
    '' => array(
      \RestfulInterface::POST => 'createEntity',
    ),
  );

  /**
   * Overrides \RestfulEntityBase::__construct()
   *
   * Set the "options" key from the plugin info, specific for file upload, with
   * the following keys:
   * - "validators": By default no validation is done on the file extensions or
   *   file size.
   * - "scheme": By default the default scheme (e.g. public, private) is used.
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);

    $options = $this->getPluginInfo('options');

    $default_values = array(
      'validators' => array(
        'file_validate_extensions' => array(),
        'file_validate_size' => array(),
      ),
      'scheme' => file_default_scheme(),
    );

    $this->plugin['options'] = drupal_array_merge_deep($default_values, $options);
  }

  /**
   * Create and save files.
   *
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   *
   * @return array
   *   Array with a list of file IDs that were created and saved.
   *
   * @throws \Exception
   */
  public function createEntity($request = NULL, stdClass $account = NULL) {
    if (!$_FILES) {
      throw new \RestfulBadRequestException('No files sent with the request.');
    }

    $options = $this->getPluginInfo('options');

    $ids = array();

    foreach ($_FILES as $file_info) {
      // Populate the $_FILES the way file_save_upload() expects.
      $name = $file_info['name'];
      foreach ($file_info as $key => $value) {
        $_FILES['files'][$key][$name] = $value;
      }

      if (!$file = file_save_upload($name, $options['validators'], $options['scheme'] . "://")) {
        throw new \Exception('An unknown error occurred while trying to save a file.');
      }

      // Change the file status from temporary to permanent.
      $file->status = FILE_STATUS_PERMANENT;
      file_save($file);

      // Required to be able to reference this file.
      file_usage_add($file, 'restful', 'files', $file->fid);

      $ids[] = $file->fid;
    }

    foreach ($ids as $id) {
      $return['list'][] = $this->viewEntity($id, $request, $account);
    }

    return $return;
  }

  /**
   * Overrides RestfulEntityBase::access().
   */
  public function access() {
    try {
      $account = $this->getAccount();
    }
    catch (\RestfulUnauthorizedException $e) {
      $account = drupal_anonymous_user();
    }
    if (module_exists('file_entity')) {
      return user_access('bypass file access') || user_access('create files');
    }

    return variable_get('restful_file_upload_allow_anonymous_user', FALSE) || $account->uid;
  }
}
