<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\file\file_upload_test\v1\FilesUploadTest__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\file\file_upload_test\v1;

use Drupal\restful\Plugin\resource\FilesUpload__1_0;

/**
 * Class FilesUploadTest__1_0
 * @package Drupal\restful_example\Plugin\Resource
 *
 * @Resource(
 *   name = "files_upload_test:1.0",
 *   resource = "files_upload_test",
 *   label = "File upload for testing",
 *   description = "A file upload wrapped with RESTful.",
 *   authenticationTypes = TRUE,
 *   dataProvider = {
 *     "entityType": "file",
 *     "options": {
 *       "scheme": "public"
 *     }
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class FilesUploadTest__1_0 extends FilesUpload__1_0 {

  /**
   * Data provider class.
   *
   * @return string
   *   The name of the class of the provider factory.
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful_test\Plugin\resource\DataProvider\DataProviderFileTest';
  }

}
