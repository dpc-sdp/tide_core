<?php

/**
 * @file
 * FeatureContext for Behat test.
 */

use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Features context for testing Test module.
 */
class FeatureContext extends DrupalContext {

  /**
   * Assert that internal browser points to specified path.
   *
   * @Then I am in the :path path
   */
  public function assertCurrentPath($path) {
    $current_path = $this->getSession()->getCurrentUrl();
    $current_path = parse_url($current_path, PHP_URL_PATH);
    $current_path = ltrim($current_path, '/');
    $current_path = $current_path == '' ? '<front>' : $current_path;

    if ($current_path != $path) {
      throw new \Exception(sprintf('Current path is "%s", but expected is "%s"', $current_path, $path));
    }
  }

  /**
   * Assert that a vocabulary exist.
   *
   * @Given vocabulary :vid with name :name exists
   */
  public function assertVocabularyExist($name, $vid) {
    $vocab = Vocabulary::load($vid);
    if (!$vocab) {
      throw new RuntimeException(sprintf('"%s" vocabulary does not exist', $vid));
    }
    elseif ($vocab->get('name') != $name) {
      throw new RuntimeException(sprintf('"%s" vocabulary name is not "%s"', $vid, $name));
    }
  }

  /**
   * Assert that a taxonomy term exist by name.
   *
   * @Given taxonomy term :name from vocabulary :vocabulary_id exists
   */
  public function assertTaxonomyTermExistsByName($name, $vid) {
    $vocab = Vocabulary::load($vid);
    if (!$vocab) {
      throw new RuntimeException(sprintf('"%s" vocabulary does not exist', $vid));
    }
    $found = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $name,
        'vid' => $vid,
      ]);

    if (count($found) == 0) {
      throw new Exception(sprintf('Taxonomy term "%s" from vocabulary "%s" does not exist', $name, $vid));
    }
  }

}
