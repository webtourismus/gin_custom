<?php

namespace Drupal\gin_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a "contact support" block.
 *
 * @Block(
 *   id = "webtourismus_support",
 *   admin_label = @Translation("Webtourismus Support"),
 *   category = @Translation("Webtourismus")
 * )
 */
class WebtourismusSupportBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'webtourismus_support_block',
      '#data' => [], /* nothing yet */
    ];
    return $build;
  }

}
