<?php

declare(strict_types = 1);

namespace Drupal\gin_custom\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the edit links for stable media entities.
 */
class WebformSubmissionLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  public const WEBFORM_SUBMISSION_LINK_WEIGHT = 3000;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface;
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $languageManagerInterface) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $languageManagerInterface;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $menuIconDefault = '/libraries/fa6/svgs/regular/envelopes-bulk.svg';
    $links = [];

    $query = $this->entityTypeManager->getStorage('webform')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('status', 'open');
    $webformIds = $query->execute();

    $webformEntities = $this->entityTypeManager->getStorage('webform')->loadMultiple($webformIds);
    /** @var \Drupal\webform\Entity\Webform $webform */
    foreach ($webformEntities as $webform) {
      $linkId = 'gin_custom.webform:' . $webform->id();
      $menuIcon = $menuIconDefault;
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

    if (count($links) >= 2) {
      foreach ($links as &$link) {
        $link['parent'] = 'gin_custom.webform_submission.link_deriver:overview';
        unset($link['menu_name']);
      }
      $links['overview'] = [
        'id' => 'overview',
        'title' => $this->t('Forms'),
        'route_name' => 'gin_custom.webform_submission_overview',
        'weight' => self::WEBFORM_SUBMISSION_LINK_WEIGHT,
        'menu_name' => 'editor',
        'description' => $menuIconDefault,
      ] + $base_plugin_definition;
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['languages'];
  }
}
