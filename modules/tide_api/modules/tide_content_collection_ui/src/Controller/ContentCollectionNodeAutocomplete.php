<?php

namespace Drupal\tide_content_collection_ui\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\tide_site_restriction\Helper;

/**
 * Controller for Tide Content Collection UI node autocomplete endpoints.
 */
class ContentCollectionNodeAutocomplete extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentCollectionNodeAutocomplete object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ContentCollectionNodeAutocomplete|AutowireTrait|static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Only allow authenticated users to access this endpoint.
   * Note: we leave this open during local development.
   */
  public static function access(AccountInterface $account): AccessResult {
    if (Settings::get('environment') === 'local') {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }

    return AccessResult::allowedIf($account->isAuthenticated() && $account->hasPermission('access content'))->setCacheMaxAge(0);
  }

  /**
   * Returns node autocomplete suggestions.
   */
  public function index(Request $request): JsonResponse {
    $results = [];
    $query = $request->query->get('q', '');

    if (!empty($query)) {
      try {
        $node_storage = $this->entityTypeManager->getStorage('node');
      } catch (InvalidPluginDefinitionException|PluginNotFoundException) {
        return new JsonResponse([]);
      }

      $query_builder = $node_storage->getQuery()
        ->condition('status', 1)
        ->condition('title', $query, 'CONTAINS')
        ->range(0, 10)
        ->accessCheck();

      $ids = $query_builder->execute();

      if (!empty($ids)) {
        $nodes = $node_storage->loadMultiple($ids);

        foreach ($nodes as $node) {
          /** @var \Drupal\node\NodeInterface $node */
          $results[] = [
            'id' => $node->id(),
            'uuid' => $node->uuid(),
            'label' => $node->getTitle(),
            'value' => $node->getTitle(),
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
