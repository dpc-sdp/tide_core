<?php

namespace Tide\Tests\Context;

use DrevOps\BehatSteps\Drupal\ContentTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\Drupal\TaxonomyTrait;
use DrevOps\BehatSteps\WaitTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use FieldTrait;
  use LinkTrait;
  use PathTrait;
  use ResponseTrait;
  use TaxonomyTrait;
  use TideCommonTrait;
  use TideExtensionsTrait;
  use TideEntityTrait;
  use WaitTrait;

}
