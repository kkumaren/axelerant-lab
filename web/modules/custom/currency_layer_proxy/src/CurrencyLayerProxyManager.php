<?php

namespace Drupal\currency_layer_proxy;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CurrencyLayerProxyManager.
 */
class CurrencyLayerProxyManager {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Create an CurrencyLayerProxyManager object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Gets the currency layer api url.
   *
   * @return string
   *   The api url.
   */
  public function getCurrencyLayerApiUrl(): string {
    return $_ENV['API_URL'] ?: '';
  }

  /**
   * Gets the currency layer api token.
   *
   * @return string
   *   The api token.
   */
  public function getCurrencyLayerApiToken(): string {
    return $_ENV['API_TOKEN'] ?: '';
  }

}
