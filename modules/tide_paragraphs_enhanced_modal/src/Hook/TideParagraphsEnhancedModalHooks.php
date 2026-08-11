<?php

namespace Drupal\tide_paragraphs_enhanced_modal\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Hook implementations for the tide paragraphs enhanced modal module.
 */
class TideParagraphsEnhancedModalHooks {

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_paragraphs_add_dialog')]
  public function themeSuggestionsParagraphsAddDialog(array $variables) {
    return ['paragraphs_add_dialog__enhanced'];
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'paragraphs_add_dialog__enhanced' => [
        'template' => 'paragraphs-add-dialog--enhanced',
        'base hook' => 'paragraphs_add_dialog',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * @see template_preprocess_paragraphs_add_dialog()
   */
  #[Hook('preprocess_paragraphs_add_dialog')]
  public function preprocessParagraphsAddDialog(&$variables) {
    if (isset($variables['buttons'])) {
      $variables['button_enhancements'] = [];
      $placeholder_icon_path = \Drupal::service('extension.list.module')->getPath('tide_paragraphs_enhanced_modal') . '/icons/placeholder.svg';
      $placeholder_icon_url = \Drupal::service('file_url_generator')->generateAbsoluteString($placeholder_icon_path);
      foreach (Element::children($variables['buttons']) as $key) {
        $bundle_machine_name = $variables['buttons'][$key]['#bundle_machine_name'];
        /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraph_type */
        $paragraph_type = ParagraphsType::load($bundle_machine_name);
        if ($paragraph_type) {
          $variables['button_enhancements'][$key] = [
            'description' => $paragraph_type->getDescription(),
            'icon_url' => $paragraph_type->getIconUrl() ?: $placeholder_icon_url,
            'icon' => $paragraph_type->getIconFile(),
          ];
        }
      }
      $variables['#attached']['library'][] = 'tide_paragraphs_enhanced_modal/paragraphs.enhanced_modal';
    }
  }

}
