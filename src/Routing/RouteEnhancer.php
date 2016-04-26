<?php

/**
 * @file
 * Contains \Drupal\restful\Routing\RouteEnhancer.
 */

namespace Drupal\restful\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;

/**
 * Class RouteEnhancer.
 *
 * @package Drupal\restful\Routing
 */
class RouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return (bool) $route->getRequirement('_bundle') && (bool) $route->getRequirement('_entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    $retrieved_bundle = $defaults[$route->getRequirement('_entity_type')]->bundle();
    $configured_bundle = $route->getRequirement('_bundle');
    if ($retrieved_bundle != $configured_bundle) {
      // If the bundle in the loaded entity does not match the bundle in the
      // route configuration (that comes from the resource_config), then throw
      // an exception.
      throw new HttpException(404, sprintf('The loaded entity bundle (%s) does not match the configured resource (%s).', $retrieved_bundle, $configured_bundle));
    }
    return $defaults;
  }

}
