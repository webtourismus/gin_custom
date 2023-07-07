<?php

declare(strict_types = 1);

namespace Drupal\gin_custom\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the edit links for stable media entities.
 */
class WebformSubmissionLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  public const WEBFORM_SUBMISSION_LINK_WEIGHT = 3000;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $query = $this->entityTypeManager->getStorage('webform')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('status', 'open');
    $webformIds = $query->execute();

    $webformEntities = $this->entityTypeManager->getStorage('webform')->loadMultiple($webformIds);
    /** @var \Drupal\webform\Entity\Webform $webform */
    foreach ($webformEntities as $webform) {
      $linkId = 'gin_custom.webform:' . $webform->id();
      $menuIcon = '/libraries/fa6/svgs/regular/envelopes-bulk.svg';
      $description = $webform->get('description');
      if (!empty($description)) {
        $description = trim(strip_tags($description));
        if (preg_match('/^\/(libraries|themes|modules|core)\/[^\"\']+\.svg$/i', $description)) {
          $menuIcon = $description;
        }
      }
      $links[$linkId] = [
        'id' => $linkId,
        'title' => $webform->label(),
        'route_name' => 'entity.webform.results_submissions',
        'route_parameters' => ['webform' => $webform->id()],
        'weight' => self::WEBFORM_SUBMISSION_LINK_WEIGHT,
        'menu_name' => 'editor',
        'description' => $menuIcon,
      ] + $base_plugin_definition;
    }

    return $links;
  }
}
