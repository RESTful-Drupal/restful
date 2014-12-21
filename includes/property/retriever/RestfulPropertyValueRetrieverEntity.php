<?php

/**
 * @file
 * Contains \RestfulPropertyValueRetrieverEntity.
 */

class RestfulPropertyValueRetrieverEntity implements \RestfulPropertyValueRetrieverInterface {

  /**
   * {@inheritdoc}
   */
  public function retrieve(array $info, \RestfulPropertySourceInterface $source) {
    $value = NULL;
    // If there is a callback defined execute it instead of a direct mapping.
    if ($info['callback']) {
      $value = \RestfulBase::executeCallback($info['callback'], array($source));
    }
    elseif (empty($info['formatter'])) {
      if ($source->isMultiple()) {
        // Multiple values.
        for ($index = 0; $index < $source->count(); $index++) {
          $value[] = $source->get($info['property'], $index);
        }
      }
      else {
        // Single value.
        $value = $source->get($info['property']);
      }
    }
    else {
      // The getter will return all values, even if it's multiple.
      $value = $source->get($info['property']);
    }


    // Execute the process callbacks.
    if ($value && $info['process_callbacks']) {
      foreach ($info['process_callbacks'] as $process_callback) {
        $value = \RestfulBase::executeCallback($process_callback, array($value));
      }
    }

    return $value;
  }

}
