<?php

namespace Drupal\tide_core\Plugin\DataType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The string data type with cacheability metadata.
 */
#[DataType(
  id: "computed_cacheable_string",
  label: new TranslatableMarkup("Computed Cacheable String"),
)]
class ComputedCacheableString extends StringData implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

}
