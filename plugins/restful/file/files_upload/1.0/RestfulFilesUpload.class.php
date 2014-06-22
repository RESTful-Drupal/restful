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
      'post' => 'createEntity',
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
    $validators = array(
      'file_validate_extensions' => array(),
      'file_validate_size' => array(),
    );

    $ids = array();
    foreach ($_FILES['files']['name'] as $field_name => $file_name) {
      if (!$file = file_save_upload($field_name, $validators, file_default_scheme() . "://")) {
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
