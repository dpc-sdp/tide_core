<?php

namespace Drupal\Tests\tide_media\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_media\Hook\TideMediaHooks;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Tide Media hook implementations.
 */
#[CoversClass(TideMediaHooks::class)]
#[Group('tide_media')]
class TideMediaHooksTest extends UnitTestCase {

  /**
   * Tests preprocessing an absolute file link with Drupal 11 utilities.
   */
  public function testPreprocessFileLink(): void {
    $file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $file_url_generator->expects($this->once())
      ->method('generateAbsoluteString')
      ->with('public://annual-report.pdf')
      ->willReturn('http://example.com/annual-report.pdf');

    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->expects($this->once())
      ->method('generate')
      ->willReturnCallback(function ($text, Url $url): GeneratedLink {
        $this->assertSame(
          '<span class="file--title">Annual report</span><span class="file--type">PDF</span><span class="file--size">1 KB</span>',
          (string) $text,
        );
        $this->assertSame('https://example.com/annual-report.pdf', $url->getUri());
        $this->assertSame([
          'class' => ['application-pdf'],
          'aria-label' => ['Annual report File type: PDF. Size: 1 KB.'],
        ], $url->getOption('attributes'));

        return (new GeneratedLink())->setGeneratedLink('<a>Annual report</a>');
      });

    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'tide_media.settings' => [
        'file_absolute_url' => TRUE,
        'force_https' => TRUE,
      ],
    ]));
    $container->set('file_url_generator', $file_url_generator);
    $container->set('link_generator', $link_generator);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $file = $this->createMock(FileInterface::class);
    $file->expects($this->once())
      ->method('getFileUri')
      ->willReturn('public://annual-report.pdf');
    $file->expects($this->once())
      ->method('getSize')
      ->willReturn(1024);
    $file->expects($this->once())
      ->method('getMimeType')
      ->willReturn('application/pdf');

    $variables = [
      'file' => $file,
      'description' => 'Annual report',
    ];
    (new TideMediaHooks())->preprocessFileLink($variables);

    $this->assertSame('<a>Annual report</a>', (string) $variables['link']);
  }

}
