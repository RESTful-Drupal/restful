<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourcePluginCollection
 */

namespace Drupal\restful\Resource;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\restful\Http\RequestInterface;

class ResourcePluginCollection extends DefaultLazyPluginCollection {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'name';

  /**
   * The request object.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * Constructs a ResourcePluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each plugin in the collection, keyed by plugin instance ID.
   * @param RequestInterface $request
   *   (optional) The request object.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array(), RequestInterface $request = NULL) {
    parent::__construct($manager, $configurations);
    $this->request = $request;
  }


  /**
   * Overwrites LazyPluginCollection::get().
   */
  public function &get($instance_id) {
    /** @var \Drupal\restful\Plugin\resource\ResourceInterface $resource */
    $resource = parent::get($instance_id);
    $resource->setConfiguration(array(
      'request' => $this->request,
    ));
    // Allow altering the resource, this way we can read the resource's
    // definition to return a different class that is using composition.
    drupal_alter('restful_resource', $resource);
    return $resource;
  }

}
