<?php

namespace Drupal\currency_layer_proxy\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\currency_layer_proxy\CurrencyLayerProxyManager;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\ClientInterface;

/**
 * Provides a resource to get current rates.
 *
 * @RestResource(
 *   id = "currency_resource",
 *   label = @Translation("Currency Layer live"),
 *   uri_paths = {
 *     "canonical" = "/api/currency",
 *     "https://www.drupal.org/link-relations/create" = "/api/currency"
 *   }
 * )
 */
class CurrencyResource extends ResourceBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The Currency Layer Proxy service.
   *
   * @var \Drupal\currency_layer_proxy\CurrencyLayerProxyManager
   */
  protected CurrencyLayerProxyManager $currencyLayerProxyManager;

  /**
   * Constructs a new CurrencyResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\currency_layer_proxy\CurrencyLayerProxyManager $currency_layer_proxy
   *   The Currency Layer Proxy service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ClientInterface $http_client, CurrencyLayerProxyManager $currency_layer_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->httpClient = $http_client;
    $this->currencyLayerProxyManager = $currency_layer_proxy;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('current_layer_proxy'),
      $container->get('http_client'),
      $container->get('currency_layer_proxy'),
    );
  }

  /**
   * Responds to GET request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function get(Request $request) {
    $response = new ResourceResponse();
    $query = $request->query->all();

    try {
      $request = $this->httpClient->get($this->currencyLayerProxyManager->getCurrencyLayerApiUrl(), [
        'headers' => [
          'Accept' => 'text/plain',
          'apikey' => $this->currencyLayerProxyManager->getCurrencyLayerApiToken(),
        ],
        'query' => http_build_query($query),
      ]);

      $cache = [
        '#cache' => ['contexts' => ['rest_queries']],
        'max-age' => 86400,
      ];

      $cacheable_metadata = CacheableMetadata::createFromRenderArray($cache);
      $response->addCacheableDependency($cacheable_metadata);

      $response->setContent($request->getBody()->getContents());
    }
    catch (BadResponseException $exception) {
      $this->logger->error($this->t('Failed to get request due to HTTP error "%error"', ['%error' => $exception->getResponse()->getStatusCode() . ' ' . $exception->getResponse()->getReasonPhrase()]));
      $response->setStatusCode('404');
    }
    catch (RequestException $exception) {
      $this->logger->error($this->t('Failed to get request due to error "%error"', ['%error' => $exception->getMessage()]));
      $response->setStatusCode('502');

    }
    return $response;
  }

}
