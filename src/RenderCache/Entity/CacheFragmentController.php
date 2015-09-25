<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\Entity\CacheFragmentController.
 */

namespace Drupal\restful\RenderCache\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class CacheFragmentController extends \EntityAPIController {

  /**
   * Creates all the caches tags from the tag collection.
   *
   * @param ArrayCollection $cache_fragments
   *   The collection of tags.
   *
   * @return CacheFragment[]
   *   An array of tag ID.
   */
  public function createCacheFragments(ArrayCollection $cache_fragments) {
    $hash = $this->generateCacheHash($cache_fragments);
    $tags = array();
    foreach ($cache_fragments as $tag_type => $tag_value) {
      $cache_fragment = new CacheFragment(array(
        'value' => $tag_value,
        'type' => $tag_type,
        'hash' => $hash,
      ), 'cache_fragment');
      if ($id = $this->save($cache_fragment)) {
        $tags[] = $cache_fragment;
      }
    }
    return $tags;
  }

  /**
   * Generated the cache hash based on the cache fragments collection.
   *
   * @param ArrayCollection $cache_fragments
   *   The collection of tags.
   *
   * @return string
   *   The generated hash.
   */
  public function generateCacheHash(ArrayCollection $cache_fragments) {
    return substr(sha1(serialize($cache_fragments->toArray())), 0, 40);
  }

  /**
   * Removes all the cache fragments.
   */
  public function wipe() {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'cache_fragment')
      ->execute();
    if (empty($results['cache_fragment'])) {
      return;
    }
    $this->delete(array_keys($results['cache_fragment']));
  }

}
