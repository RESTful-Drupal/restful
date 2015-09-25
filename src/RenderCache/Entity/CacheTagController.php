<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\Entity\CacheTagController.
 */

namespace Drupal\restful\RenderCache\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class CacheTagController extends \EntityAPIController {

  /**
   * Creates all the caches tags from the tag collection.
   *
   * @param ArrayCollection $cache_tags
   *   The collection of tags.
   *
   * @return CacheTag[]
   *   An array of tag ID.
   */
  public function createCacheTags(ArrayCollection $cache_tags) {
    $hash = $this->generateCacheHash($cache_tags);
    $tags = array();
    foreach ($cache_tags as $tag_type => $tag_value) {
      $cache_tag = new CacheTag(array(
        'value' => $tag_value,
        'type' => $tag_type,
        'hash' => $hash,
      ), 'cache_tag');
      if ($id = $this->save($cache_tag)) {
        $tags[] = $cache_tag;
      }
    }
    return $tags;
  }

  /**
   * Generated the cache hash based on the cache tags collection.
   *
   * @param ArrayCollection $cache_tags
   *   The collection of tags.
   *
   * @return string
   *   The generated hash.
   */
  public function generateCacheHash(ArrayCollection $cache_tags) {
    return sha1(serialize($cache_tags->toArray()));
  }

}
