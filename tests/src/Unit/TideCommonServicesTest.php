<?php

namespace Drupal\Tests\tide_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tide_core\TideCommonServices;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Main test class for common functions.
 */
#[CoversMethod(TideCommonServices::class, 'sanitiseFileName')]
class TideCommonServicesTest extends UnitTestCase {

  #[DataProvider('fileNameProvider')]
  public function testSanitiseFileName($value, $replacement, $include_in_pattern, $expected) {
    $tideCommonServices = new TideCommonServices();
    $filename = $tideCommonServices->sanitiseFileName($value, $replacement, $include_in_pattern);
    $this->assertEquals($expected, $filename);
  }

  /**
   * Data provider of test sanitise.
   *
   * @return array
   *   Array of values.
   */
  public static function fileNameProvider() {
    return [
      [
        'value' => 'file with spaces only.pdf',
        'replacement' => '-',
        'include_in_pattern' => FALSE,
        'expected' => 'file-with-spaces-only.pdf',
      ],
      [
        'value' => 'file with spaces only.pdf',
        'replacement' => '-',
        'include_in_pattern' => TRUE,
        'expected' => 'file-with-spaces-only.pdf',
      ],
      [
        'value' => 'file - with - spaces - only.pdf',
        'replacement' => '-',
        'include_in_pattern' => FALSE,
        'expected' => 'file---with---spaces---only.pdf',
      ],
      [
        'value' => 'file - with - spaces - only.pdf',
        'replacement' => '-',
        'include_in_pattern' => TRUE,
        'expected' => 'file-with-spaces-only.pdf',
      ],
      [
        'value' => 'file - with- before- spaces and -after -space.pdf',
        'replacement' => '-',
        'include_in_pattern' => TRUE,
        'expected' => 'file-with-before-spaces-and-after-space.pdf',
      ],
      [
        'value' => 'file - with + spaces _ other ~ symbols.pdf',
        'replacement' => '-',
        'include_in_pattern' => FALSE,
        'expected' => 'file---with-+-spaces-_-other-~-symbols.pdf',
      ],
      [
        'value' => 'file - with + spaces _ other ~ symbols.pdf',
        'replacement' => '-',
        'include_in_pattern' => TRUE,
        'expected' => 'file-with-+-spaces-_-other-~-symbols.pdf',
      ],
      [
        'value' => 'site/2020-02/files/file - with - spaces - only.pdf',
        'replacement' => '-',
        'include_in_pattern' => TRUE,
        'expected' => 'site/2020-02/files/file-with-spaces-only.pdf',
      ],
    ];
  }

}
