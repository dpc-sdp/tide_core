<?php

namespace Drupal\tide_dashboard\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Hook implementations for the tide dashboard module.
 */
class TideDashboardHooks {

  /**
   * Implements hook_toolbar_alter().
   *
   * Hide Workbench menu from users without the custom permission.
   *
   * @see workbench_toolbar()
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(&$items) {
    $items['administration']['#attached']['library'][] = 'tide_dashboard/toolbar.icons';
    $user = \Drupal::currentUser();
    if (!$user->hasPermission('access workbench menu')) {
      unset($items['workbench']);
    }
  }

  /**
   * Implements hook_user_login().
   */
  #[Hook('user_login')]
  public function userLogin() {
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if (($request->query->has('destination')) === FALSE) {
      $request->query->set('destination', '/admin/workbench');
    }
  }

  /**
   * Alters the system breadcrumb for authenticated users.
   *
   * Replaces the default "Home" breadcrumb link (which normally points to
   * the <front> route) with a link to '/admin/workbench' for logged-in users.
   *
   * This ensures that administrators and editors navigating within the
   * backend are directed back to the Workbench dashboard instead of the
   * public homepage when using breadcrumb navigation.
   *
   * Anonymous users are excluded from this alteration.
   */
  #[Hook('system_breadcrumb_alter')]
  public function systemBreadcrumbAlter(Breadcrumb &$breadcrumb, RouteMatchInterface $route_match, array $context) {
    if (\Drupal::currentUser()->isAnonymous()) {
      return;
    }

    $links = $breadcrumb->getLinks();

    if (!empty($links)) {
      // Check if the first link is the Home link.
      if ($links[0]->getUrl()->isRouted() && $links[0]->getUrl()->getRouteName() === '<front>') {

        $new_url = Url::fromUserInput('/admin/workbench');
        $new_link = Link::fromTextAndUrl($links[0]->getText(), $new_url);

        // Swap the first link to workbench.
        $links[0] = $new_link;

        $reflection = new \ReflectionClass($breadcrumb);
        $property = $reflection->getProperty('links');
        $property->setAccessible(TRUE);
        $property->setValue($breadcrumb, $links);
      }
    }
  }

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData() {
    $data = [];

    $data['views']['views_tide_dashboard_admin_content_search_form'] = [
      'title' => t('Admin Content Search form'),
      'help' => t('Insert the Admin Content Search form inside an area.'),
      'area' => [
        'id' => 'views_tide_dashboard_admin_content_search_form',
      ],
    ];

    return $data;
  }

}
