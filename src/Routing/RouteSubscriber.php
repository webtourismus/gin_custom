<?php

namespace Drupal\gin_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // we want the editor menu to use /admin
    // to access the original /admin use /admin/manage instead
    if ($route = $collection->get('system.admin')) {
      $route->setPath('/admin/manage');
    }
  }
}
