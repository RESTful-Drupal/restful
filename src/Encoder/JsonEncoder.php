<?php

/**
 * @file
 * Contains \Drupal\restful\Encoder\JsonEncoder.
 */

namespace Drupal\restful\Encoder;

use Drupal\hal\Encoder\JsonEncoder as HalJsonEncoder;

/**
 * Encodes HAL data in JSON.
 *
 * Simply respond to application/hal+json format requests using JSON encoder.
 */
class JsonEncoder extends HalJsonEncoder {}
