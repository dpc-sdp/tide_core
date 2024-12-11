<?php

declare(strict_types=1);

namespace Drupal\tide_search\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Defines a class for a Text Field with Keyword and Prefix Subfields data type.
 *
 * @SearchApiDataType(
 *   id = "tide_search_text_field_with_keyword_and_prefix",
 *   label = @Translation("Text Field with Keyword and Prefix Subfields"),
 *   description = @Translation("Text Field with Keyword and Prefix Subfields"),
 *   fallback_type = "text",
 * )
 */
final class TextFieldWithKeywordAndPrefix extends TextDataType {

}
