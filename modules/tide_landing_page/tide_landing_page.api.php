<?php

/**
 * @file
 * Hooks related to tide_landing_page API.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the card_link_enhancer doUndoTransform.
 *
 * @param object $data
 *   The JSON data.
 * @param Shaper\Util\Context $context
 *   To manage contextual conditions.
 */
function hook_tide_card_link_enhancer_undo_transform_alter(&$data, Shaper\Util\Context $context) {
  $data['alias'];
}

/**
 * @} End of "addtogroup hooks".
 */
