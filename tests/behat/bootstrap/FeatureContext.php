<?php

namespace Tide\Tests\Context;

use DrevOps\BehatSteps\ContentTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\JsTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\MediaTrait;
use DrevOps\BehatSteps\MenuTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\TaxonomyTrait;
use DrevOps\BehatSteps\VisibilityTrait;
use DrevOps\BehatSteps\WatchdogTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use FieldTrait;
  use JsTrait;
  use LinkTrait;
  use MediaTrait;
  use MenuTrait;
  use PathTrait;
  use ResponseTrait;
  use TaxonomyTrait;
  use TideCommonTrait;
  use TideExtensionsTrait;
  use TideEntityTrait;
  use VisibilityTrait;
  use WatchdogTrait;

}
