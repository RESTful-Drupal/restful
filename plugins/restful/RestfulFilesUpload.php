<?php

/**
 * @file
 * Contains RestfulFilesUpload.
 */

class RestfulFilesUpload extends \RestfulEntityBase {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        \RestfulInterface::POST => 'createEntity',
      ),
    );
  }

  /**
   * Overrides \RestfulEntityBase::__construct()
   *
   * Set the "options" key from the plugin info, specific for file upload, with
   * the following keys:
   * - "validators": By default no validation is done on the file extensions or
   *   file size.
   * - "scheme": By default the default scheme (e.g. public, private) is used.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);

    if (!$options = $this->getPluginKey('options')) {
      $options = array();
    }

    $default_values = array(
      'validators' => array(
        'file_validate_extensions' => array(),
        'file_validate_size' => array(),
      ),
      'scheme' => file_default_scheme(),
      'replace' => FILE_EXISTS_RENAME,
    );

    $this->setPluginKey('options', drupal_array_merge_deep($default_values, $options));
  }

  /**
   * Create and save files.
   *
   * @return array
   *   Array with a list of file IDs that were created and saved.
   *
   * @throws \Exception
   */
  public function createEntity() {
    if (!$_FILES) {
      throw new \RestfulBadRequestException('No files sent with the request.');
    }

    $ids = array();

    foreach ($_FILES as $file_info) {
      // Populate the $_FILES the way file_save_upload() expects.
      $name = $file_info['name'];
      foreach ($file_info as $key => $value) {
        $_FILES['files'][$key][$name] = $value;
      }

      $file = $this->fileSaveUpload($name);

      // Change the file status from temporary to permanent.
      $file->status = FILE_STATUS_PERMANENT;
      file_save($file);

      // Required to be able to reference this file.
      file_usage_add($file, 'restful', 'files', $file->fid);

      $ids[] = $file->fid;
    }

    $return = array();
    foreach ($ids as $id) {
      $return[] = $this->viewEntity($id);
    }

    return $return;
  }

  /**
   * Overrides RestfulEntityBase::access().
   *
   * If "File entity" module exists, determine access by its provided permissions
   * otherwise, check if variable is set to allow anonymous users to upload.
   * Defaults to authenticated user.
   */
  public function access() {
    // The getAccount method may return a RestfulUnauthorizedException when an
    // authenticated user cannot be found. Since this is called from the access
    // callback, not from the page callback we need to catch the exception.
    try {
      $account = $this->getAccount();
    }
    catch (\RestfulUnauthorizedException $e) {
      // If a user is not found then load the anonymous user to check
      // permissions.
      $account = drupal_anonymous_user();
    }
    if (module_exists('file_entity')) {
      return user_access('bypass file access', $account) || user_access('create files', $account);
    }

    return (variable_get('restful_file_upload_allow_anonymous_user', FALSE) || $account->uid) && parent::access();
  }

  /**
   * An adaptation of file_save_upload() that includes more verbose errors.
   *
   * @param string $source
   *   A string specifying the filepath or URI of the uploaded file to save.
   *
   * @return stdClass
   *   The saved file object.
   *
   * @throws \RestfulBadRequestException
   * @throws \RestfulServiceUnavailable
   *
   * @see file_save_upload()
   */
  protected function fileSaveUpload($source) {
    static $upload_cache;

    $account = $this->getAccount();
    $options = $this->getPluginKey('options');

    $validators = $options['validators'];
    $destination = $options['scheme'] . "://";
    $replace = $options['replace'];

    // Return cached objects without processing since the file will have
    // already been processed and the paths in _FILES will be invalid.
    if (isset($upload_cache[$source])) {
      return $upload_cache[$source];
    }

    // Make sure there's an upload to process.
    if (empty($_FILES['files']['name'][$source])) {
      return NULL;
    }

    // Check for file upload errors and return FALSE if a lower level system
    // error occurred. For a complete list of errors:
    // See http://php.net/manual/features.file-upload.errors.php.
    switch ($_FILES['files']['error'][$source]) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        $message = format_string('The file %file could not be saved, because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $_FILES['files']['name'][$source], '%maxsize' => format_size(file_upload_max_size())));
        throw new \RestfulBadRequestException($message);

      case UPLOAD_ERR_PARTIAL:
      case UPLOAD_ERR_NO_FILE:
        $message = format_string('The file %file could not be saved, because the upload did not complete.', array('%file' => $_FILES['files']['name'][$source]));
        throw new \RestfulBadRequestException($message);

      case UPLOAD_ERR_OK:
        // Final check that this is a valid upload, if it isn't, use the
        // default error handler.
        if (is_uploaded_file($_FILES['files']['tmp_name'][$source])) {
          break;
        }

      // Unknown error
      default:
        $message = format_string('The file %file could not be saved. An unknown error has occurred.', array('%file' => $_FILES['files']['name'][$source]));
        throw new \RestfulServiceUnavailable($message);
    }

    // Begin building file object.
    $file = new stdClass();
    $file->uid      = $account->uid;
    $file->status   = 0;
    $file->filename = trim(drupal_basename($_FILES['files']['name'][$source]), '.');
    $file->uri      = $_FILES['files']['tmp_name'][$source];
    $file->filemime = file_get_mimetype($file->filename);
    $file->filesize = $_FILES['files']['size'][$source];

    $extensions = '';
    if (isset($validators['file_validate_extensions'])) {
      if (isset($validators['file_validate_extensions'][0])) {
        // Build the list of non-munged extensions if the caller provided them.
        $extensions = $validators['file_validate_extensions'][0];
      }
      else {
        // If 'file_validate_extensions' is set and the list is empty then the
        // caller wants to allow any extension. In this case we have to remove the
        // validator or else it will reject all extensions.
        unset($validators['file_validate_extensions']);
      }
    }
    else {
      // No validator was provided, so add one using the default list.
      // Build a default non-munged safe list for file_munge_filename().
      $extensions = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp';
      $validators['file_validate_extensions'] = array();
      $validators['file_validate_extensions'][0] = $extensions;
    }

    if (!empty($extensions)) {
      // Munge the filename to protect against possible malicious extension hiding
      // within an unknown file type (ie: filename.html.foo).
      $file->filename = file_munge_filename($file->filename, $extensions);
    }

    // Rename potentially executable files, to help prevent exploits (i.e. will
    // rename filename.php.foo and filename.php to filename.php.foo.txt and
    // filename.php.txt, respectively). Don't rename if 'allow_insecure_uploads'
    // evaluates to TRUE.
    if (!variable_get('allow_insecure_uploads', 0) && preg_match('/\.(php|pl|py|cgi|asp|js)(\.|$)/i', $file->filename) && (substr($file->filename, -4) != '.txt')) {
      $file->filemime = 'text/plain';
      $file->uri .= '.txt';
      $file->filename .= '.txt';
      // The .txt extension may not be in the allowed list of extensions. We have
      // to add it here or else the file upload will fail.
      if (!empty($extensions)) {
        $validators['file_validate_extensions'][0] .= ' txt';

        // Unlike file_save_upload() we don't need to let the user know that
        // for security reasons, your upload has been renamed, since RESTful
        // will return the file name in the response.
      }
    }

    // If the destination is not provided, use the temporary directory.
    if (empty($destination)) {
      $destination = 'temporary://';
    }

    // Assert that the destination contains a valid stream.
    $destination_scheme = file_uri_scheme($destination);
    if (!$destination_scheme || !file_stream_wrapper_valid_scheme($destination_scheme)) {
      $message = format_string('The file could not be uploaded, because the destination %destination is invalid.', array('%destination' => $destination));
      throw new \RestfulServiceUnavailable($message);
    }

    $file->source = $source;
    // A URI may already have a trailing slash or look like "public://".
    if (substr($destination, -1) != '/') {
      $destination .= '/';
    }
    $file->destination = file_destination($destination . $file->filename, $replace);
    // If file_destination() returns FALSE then $replace == FILE_EXISTS_ERROR and
    // there's an existing file so we need to bail.
    if ($file->destination === FALSE) {
      $message = format_string('The file %source could not be uploaded because a file by that name already exists in the destination %directory.', array('%source' => $source, '%directory' => $destination));
      throw new \RestfulServiceUnavailable($message);
    }

    // Add in our check of the the file name length.
    $validators['file_validate_name_length'] = array();

    // Call the validation functions specified by this function's caller.
    $errors = file_validate($file, $validators);

    // Check for errors.
    if (!empty($errors)) {
      $message = format_string('The specified file %name could not be uploaded.', array('%name' => $file->filename));
      if (count($errors) > 1) {
        $message .= theme('item_list', array('items' => $errors));
      }
      else {
        $message .= ' ' . array_pop($errors);
      }

      throw new \RestfulServiceUnavailable($message);
    }

    // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary
    // directory. This overcomes open_basedir restrictions for future file
    // operations.
    $file->uri = $file->destination;
    if (!drupal_move_uploaded_file($_FILES['files']['tmp_name'][$source], $file->uri)) {
      watchdog('file', 'Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $file->filename, '%destination' => $file->uri));
      $message = 'File upload error. Could not move uploaded file.';
      throw new \RestfulServiceUnavailable($message);
    }

    // Set the permissions on the new file.
    drupal_chmod($file->uri);

    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('uri' => $file->uri));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->fid;
      }
    }

    // If we made it this far it's safe to record this file in the database.
    if ($file = file_save($file)) {
      // Add file to the cache.
      $upload_cache[$source] = $file;
      return $file;
    }

    // Something went wrong, so throw a general exception.
    throw new \RestfulServiceUnavailable('Unknown error has occurred.');
  }
}
