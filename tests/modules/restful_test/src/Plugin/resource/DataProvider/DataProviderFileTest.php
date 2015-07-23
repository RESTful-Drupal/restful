<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\DataProvider\DataProviderFileTest.
 */

namespace Drupal\restful_test\Plugin\resource\DataProvider;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderFile;

class DataProviderFileTest extends DataProviderFile {

  /**
   * Helper function that checks if a file was uploaded via a POST request.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return bool
   *   TRUE if the file is uploaded. FALSE otherwise.
   */
  protected static function isUploadedFile($filename) {
    return variable_get('restful_insecure_uploaded_flag', FALSE) || is_uploaded_file($filename);
  }

  /**
   * Helper function that moves an uploaded file.
   *
   * @param string $filename
   *   The path of the file to move.
   * @param string $uri
   *   The path where to move the file.
   *
   * @return bool
   *   TRUE if the file was moved. FALSE otherwise.
   */
  protected static function moveUploadedFile($filename, $uri) {
    if (drupal_move_uploaded_file($filename, $uri)) {
      return TRUE;
    }
    return variable_get('restful_insecure_uploaded_flag', FALSE) && (bool) file_unmanaged_move($filename, $uri);
  }

}
