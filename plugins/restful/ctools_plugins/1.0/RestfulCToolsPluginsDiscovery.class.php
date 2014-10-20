<?php

/**
 * @file
 * Contains \RestfulQueryVariable
 */

class RestfulCToolsPluginsDiscovery extends \RestfulDataProviderCToolsPlugins {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'name' => array(
        'property' => 'name',
      ),
      'description' => array(
        'property' => 'description',
      ),
      'resource' => array(
        'property' => 'resource',
      ),
      'major_version' => array(
        'property' => 'major_version',
      ),
      'minor_version' => array(
        'property' => 'minor_version',
      ),
    );
  }

}
