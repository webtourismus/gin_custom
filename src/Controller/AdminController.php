<?php

declare(strict_types = 1);

namespace Drupal\gin_custom\Controller;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminController extends ControllerBase {

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * @inheritDoc
   */
  public function __construct(MenuActiveTrailInterface $menu_active_trail, MenuLinkTreeInterface $menu_tree) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuTree = $menu_tree;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.active_trail'),
      $container->get('menu.link_tree'),
    );
  }

  /**
   * To get the original core admin menu into the editor toolbar.
   */
  public function drupalAdminToolbar() {
    return new RedirectResponse('/admin/manage');
  }

  public function dashboard() {
    $tiles = [];
    $query = $this->entityTypeManager()->getStorage('block')->getQuery();
    $query->condition('id', 'gin_dashboard_', 'STARTS_WITH');
    $query->condition('status', true);
    $blockIds = $query->execute();
    foreach ($blockIds as $blockId) {
      $block = $this->entityTypeManager()->getStorage('block')->load($blockId);
      $tiles[$blockId] = $this->entityTypeManager()->getViewBuilder('block')->view($block);
    }
    $output = [
      '#theme' => 'gin_custom_dashboard',
      '#tiles' => $tiles,
      '#toolbar_menu_block' => $this->toolbarMenuBlockPage('editor'),
    ];
    return $output;
  }

  /**
   * @see \Drupal\system\Controller\SystemController::systemAdminMenuBlockPage()
   */
  public function toolbarMenuBlockPage(string $menu, string $subtree = NULL) {
    // Only find the children of this link.
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($subtree)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $this->menuTree->load($menu, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    foreach ($tree as $key => $element) {
      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        // @todo Bubble cacheability metadata of both accessible and
        //   inaccessible links. Currently made impossible by the way admin
        //   blocks are rendered.
        continue;
      }
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link->getPluginId() == 'gin_custom.dashboard') {
        continue;
      }

      // Add toolbar icons inline into the title
      $menuIcon = '/libraries/fa6/svgs/regular/file.svg';
      $possiblyAnIcon = NULL;
      $options = $link->getOptions();
      if (!empty($options['attributes']['class'])) {
        foreach ($options['attributes']['class'] as $idx => $possiblyAnIcon) {
          if (preg_match('/\/(libraries|themes|modules|core)\/[^\"\'\)]+\.svg/i', trim($possiblyAnIcon), $matches)) {
            $menuIcon = $matches[0];
            break 1;
          }
        }
      }
      // Add descriptions used for icons into the title (webform, views)
      $description = $link->getDescription();
      if (is_string($description) && preg_match('/\/(libraries|themes|modules|core)\/[^\"\'\)]+\.svg/i', $description, $matches)) {
        $menuIcon = $matches[0];
        $description = NULL;
      }
      $title = [
        '#theme' => 'toolbar_menu_block_page_item',
        '#icon' => $menuIcon,
        '#title' => $link->getTitle(),
      ];
      $content[$key]['title'] = $title;
      $content[$key]['description'] = $description;
      $url = $link->getUrlObject();
      $urlAttributes = $url->getOption('attributes') ?? [];
      unset($urlAttributes['title']);
      if ($possiblyAnIcon && is_array($urlAttributes['class']) && in_array($possiblyAnIcon, $urlAttributes['class'])) {
        // remove the toolbar link icon, which would override the chevron icon from the menu block link
        $urlAttributes['class'] = array_diff($urlAttributes['class'], [$possiblyAnIcon]);
      }
      $url->setOption('attributes', $urlAttributes);
      $content[$key]['url'] = $url;
    }
    ksort($content);
    if ($content) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
        '#cache' => [
          'tags' => ["user:{$this->currentUser()->id()}"],
        ]
      ];
    }
    else {
      $output = [
        '#markup' => $this->t('You do not have any administrative items.'),
        '#cache' => [
          'tags' => ["user:{$this->currentUser()->id()}"],
        ]
      ];
    }
    return $output;
  }
}
