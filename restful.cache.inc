<?php

/**
 * @file
 * Procedural implementations for cache related features.
 */
use Drupal\restful\RenderCache\RenderCache;

/**
 * Clears all caches and associated fragments.
 */
function restful_clear_caches() {
  /* @var \Drupal\restful\RenderCache\Entity\CacheFragmentController $controller */
  $controller = entity_get_controller('cache_fragment');
  $controller->wipe();
  cache_clear_all('*', RenderCache::CACHE_BIN, TRUE);
  drupal_set_message(t('RESTful caches were successfully cleared.'));
}

/**
 * Implements hook_flush_caches().
 */
function restful_flush_caches() {
  if (!variable_get('restful_clear_on_cc_all', FALSE)) {
    return array();
  }
  // Delete all the cache fragments.
  /* @var \Drupal\restful\RenderCache\Entity\CacheFragmentController $controller */
  $controller = entity_get_controller('cache_fragment');
  $controller->wipe();
  return array(RenderCache::CACHE_BIN);
}

/**
 * Menu callback; Admin settings form.
 */
function restful_admin_cache_settings($form_state) {
  $form = array();
  $form['restful_page_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Page cache'),
    '#description' => t('RESTful can leverage page cache, this will boost your performace for anonymous traffic. !link to start caching responses. Status: <strong>@status</strong>. <strong>CAUTION:</strong> If your resources are using authentication providers other than cookie, you will want to turn this off. Otherwise you may get cached anonymous values for your authenticated GET requests.', array(
      '!link' => l(t('Enable page cache'), 'admin/config/development/performance'),
      '@status' => variable_get('cache', FALSE) ? t('Enabled') : t('Disabled'),
    )),
    '#disabled' => !variable_get('cache', FALSE),
    '#default_value' => variable_get('restful_page_cache', FALSE) && variable_get('cache', FALSE),
  );

  $form['restful_render_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Cache results'),
    '#description' => t('When enabled any resource that has not explicitly disabled the caching will be cached. Note that the first hit may result with slower response, although the next ones would be significantly faster. This is different from the page cache in the sense that it acts at the row level (a single entity, a single database row, ...), therefore allowing you to assemble non cached pages with the cached bits faster.'),
    '#default_value' => variable_get('restful_render_cache', FALSE),
  );

  $form['clear_restful'] = array(
    '#submit' => array('restful_clear_caches'),
    '#type' => 'submit',
    '#value' => t('Clear render caches'),
    '#disabled' => !variable_get('restful_render_cache', FALSE) && user_access('restful clear render caches'),
  );

  $form['restful_clear_on_cc_all'] = array(
    '#type' => 'checkbox',
    '#title' => t('Clear on global flush'),
    '#description' => t("Check this box to clear the render caches when clearing Drupal's caches. In general the render caches are more robust than the TTL based caches. The recommended value is unchecked."),
    '#default_value' => variable_get('restful_clear_on_cc_all', FALSE),
  );

  $form['restful_fast_cache_clear'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fast cache clear'),
    '#description' => t('A lot of cache fragment entries may be created by default. This may cause your cache clears to be slow. By checking this checkbox the cache fragments are deleted from the database in a fast manner. As a trade-in, no hook_entity_delete will be fired for the cache fragment entities. This is OK in the vast majority of the cases. You can mitigate the number of generated fragments by overriding the "getCacheContext" method in your data provider.'),
    '#default_value' => variable_get('restful_fast_cache_clear', TRUE),
  );

  return system_settings_form($form);
}
