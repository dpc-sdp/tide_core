<?php

namespace Drupal\tide_api\Event;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class Get Route Event.
 *
 * @package Drupal\tide_api\Event
 */
class GetRouteEvent extends Event {

  /**
   * The Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Response Status code.
   *
   * @var int
   */
  protected $code = Response::HTTP_OK;

  /**
   * The JSON Response array.
   *
   * @var array
   */
  protected $jsonResponse;

  /**
   * The Entity retrieved from the route.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity = NULL;

  /**
   * The cacheable metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheableMetadata;

  /**
   * GetRouteEvent constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   * @param array $json_response
   *   The JSON Response array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity object.
   * @param int $code
   *   The status code.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   The cache metadata.
   */
  public function __construct(Request $request, array $json_response, EntityInterface $entity = NULL, $code = Response::HTTP_OK, CacheableMetadata $cacheable_metadata = NULL) {
    $this->request = $request;
    $this->setJsonResponse($json_response);
    $this->entity = $entity;
    $this->setCode($code);
    $this->cacheableMetadata = $cacheable_metadata ?? new CacheableMetadata();
  }

  /**
   * Returns the current Request object of the event.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The Request object.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Returns the current JSON Response array.
   *
   * @return array
   *   The JSON Response array.
   */
  public function getJsonResponse() {
    return $this->jsonResponse;
  }

  /**
   * Set the JSON Response array.
   *
   * @param array $jsonResponse
   *   The new JSON Response array.
   */
  public function setJsonResponse(array $jsonResponse) {
    $this->jsonResponse = $jsonResponse;
  }

  /**
   * Get the Response status code.
   *
   * @return int
   *   The status code.
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Set a new Response status code.
   *
   * @param int $code
   *   The new status code.
   */
  public function setCode($code) {
    $this->code = $code;
  }

  /**
   * Is the response OK?
   *
   * @return bool
   *   TRUE if ok.
   */
  public function isOk() {
    return $this->code == Response::HTTP_OK;
  }

  /**
   * Is the response bad?
   *
   * @return bool
   *   TRUE if bad request.
   */
  public function isBadRequest() {
    return $this->code == Response::HTTP_BAD_REQUEST;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropagationStopped(): bool {
    return !$this->isOk() || parent::isPropagationStopped();
  }

  /**
   * Returns the current Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns the cacheable metadata.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The metadata.
   */
  public function getCacheableMetadata() {
    return $this->cacheableMetadata;
  }

}
