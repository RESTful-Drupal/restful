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

    $validators = array(
      'file_validate_extensions' => array(),
      'file_validate_size' => array(),
    );

    $ids = array();

    foreach ($_FILES as $file_info) {
      // Populate the $_FILES the way file_save_upload() expects.
      $name = $file_info['name'];
      foreach ($file_info as $key => $value) {
        $_FILES['files'][$key][$name] = $value;
      }

      if (!$file = file_save_upload($name, $validators, file_default_scheme() . "://")) {
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
}
