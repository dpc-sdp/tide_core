<?php


namespace Drupal\tide_core\Commands;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
class TideDeleteOrphanedParagraphsTest extends DrushCommands {

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * Constructs a new object.
   *
   * @param  \Drupal\Core\KeyValueStore\KeyValueFactoryInterface  $keyValue
   *   The key value factory.
   */
  public function __construct(
    KeyValueFactoryInterface $keyValue
  ) {
    $this->keyValue = $keyValue;
  }

  /**
   * Delete orphaned paragraphs.
   *
   * @param  int  $limit
   *   The maximum number of paragraphs to process.
   *
   * @param string $number_days_ago
   *   The number of days to purge the data.
   * @command tide_core:delete-orphaned-paragraphs-test
   * @aliases dop
   * @usage tide_core:delete-orphaned-paragraphs-test 5
   */
  public function deleteOrphanedParagraphsTest($limit = 5, $number_days_ago = '-90 days')
  {
    $kv = $this->keyValue->get('delete-orphaned-paragraphs-test');
    $last_checked_p_id = $kv->get('last_checked_p_id', 0);
    $thirty_days_ago = strtotime($number_days_ago);
    $database = \Drupal::database()
      ->select('paragraphs_item_field_data', 'p')
      ->fields('p', ['id'])
      ->where("p.id > :last_checked_p_id", [':last_checked_p_id' => $last_checked_p_id])
      ->where("p.created < :thirty_days_ago", [':thirty_days_ago' => $thirty_days_ago])
      ->orderBy("p.id")
      ->range(0, $limit);
    $results = $database->execute();
    $paragraph_ids = $results->fetchCol();
    if (empty($paragraph_ids)) {
      $this->output()->writeln('No paragraphs found.');
      return;
    }

    foreach ($paragraph_ids as $paragraph_id) {
      $paragraph_entity = Paragraph::load($paragraph_id);
      if ($paragraph_entity instanceof Paragraph && $paragraph_entity->getParentEntity() === NULL) {
        $paragraph_entity->delete();
        $this->output()->writeln('Paragraphs deleted.');
      }
    }
    $kv->set('last_checked_p_id', max($paragraph_ids));
  }
}
