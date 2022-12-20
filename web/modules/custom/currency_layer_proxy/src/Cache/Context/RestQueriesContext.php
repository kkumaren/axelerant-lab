<?php

namespace Drupal\currency_layer_proxy\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RestQueries.
 */
class RestQueriesContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * Constructs an RestQueries.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    parent::__construct($request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Rest Queries.');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();

    $queries = $request->query->all();
    ksort($queries);
    $contexts = [];
    foreach ($queries as $key => $query) {
      if (!empty($query)) {
        $items = explode(',', $query);
        asort($items);
        $contexts[$key] = $key . ':' . implode(',', $items);
      }
    }
    return implode('|', $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
