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
    );
  }

}
