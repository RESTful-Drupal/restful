<?php

/**
 * @file
 * Contains \RestfulPropertyMetadataRetrieverEntity.
 */

class RestfulPropertyMetadataRetrieverEntity implements \RestfulPropertyValueRetrieverInterface {

  /**
   * {@inheritdoc}
   */
  public function retrieve(array $info, \RestfulPropertySourceInterface $source) {
    $metadata = NULL;
    // If there is a callback or there is no resource, then skip.
    if ($info['callback'] || empty($info['resource']) || !empty($info['formatter'])) {
      return NULL;
    }

    // Make sure that the getter returns all the information needed for the
    // metadata.
    $context = $source->getContext();
    foreach (array_keys($context['resource']) as $bundle) {
      $context['resource'][$bundle]['metadata_view'] = TRUE;
    }
    $source->setContext($context);

    if ($source->isMultiple()) {
      // Multiple values.
      for ($index = 0; $index < $source->count(); $index++) {
        $metadata[] = $source->get($info['property'], $index);
      }
    }
    else {
      // Single value.
      $metadata = $source->get($info['property']);
    }

    return $metadata;
  }

}
