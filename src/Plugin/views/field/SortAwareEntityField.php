<?php

namespace Drupal\tide_core\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityField;

/**
 * Entity field handler that delegates click sorting to the sort handler.
 *
 * Drupal core's EntityField::clickSort() implements its own simple sorting
 * logic (ORDER BY raw field value) which completely ignores any custom sort
 * plugin defined for the same field.
 *
 * This field handler fixes the issue by checking whether a matching sort
 * handler exists in the view. If found, it delegates click sorting to that
 * handler's query() method, ensuring table header click sorting respects
 * the custom sort logic.
 *
 * Usage: In hook_views_data_alter(), set the field handler ID to
 * 'sort_aware_field' for any entity field that has a custom sort plugin.
 *
 * @see https://www.drupal.org/project/drupal/issues/3174392
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("sort_aware_field")]
class SortAwareEntityField extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    foreach ($this->view->sort as $sort_handler) {
      if ($this->isSortHandlerMatch($sort_handler)) {
        $sort_handler->options['order'] = $order;
        $sort_handler->setRelationship();
        $sort_handler->query($this->view->display_handler->useGroupBy());
        return;
      }
    }

    // No matching sort handler found, fall back to default behavior.
    parent::clickSort($order);
  }

  /**
   * Determines whether a sort handler matches this field handler.
   */
  protected function isSortHandlerMatch($sort_handler): bool {
    // Skip broken handlers.
    if (!empty($sort_handler->broken)) {
      return FALSE;
    }

    // 1. Direct match by table and real field name.
    if (isset($sort_handler->table) && isset($sort_handler->realField)) {
      if ($sort_handler->table === $this->table && $sort_handler->realField === $this->realField) {
        return TRUE;
      }
    }

    // 2. Match by entity field_name definition for entity fields which may
    //    use different table aliases but refer to the same entity field.
    if (isset($sort_handler->definition['field_name']) && isset($this->definition['field_name'])) {
      if ($sort_handler->definition['field_name'] === $this->definition['field_name']
        && isset($sort_handler->definition['entity_type']) && isset($this->definition['entity_type'])
        && $sort_handler->definition['entity_type'] === $this->definition['entity_type']) {
        return TRUE;
      }
    }

    // 3. Relationship match: the sort handler uses a relationship that was
    //    built from this field. This covers entity reference fields where the
    //    sort operates on the referenced entity's table (e.g. field_topic
    //    field on node__field_topic, with a sort on taxonomy_term_field_data
    //    via a relationship built from field_topic).
    if (!empty($sort_handler->options['relationship']) && $sort_handler->options['relationship'] !== 'none') {
      $relationship_id = $sort_handler->options['relationship'];
      if (isset($this->view->relationship[$relationship_id])) {
        $rel_handler = $this->view->relationship[$relationship_id];
        if ($rel_handler->table === $this->table && $rel_handler->realField === $this->realField) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
