<?php

namespace Drupal\Tests\tide_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tide_core\Controller\SystemInfoController;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the SystemInfoController.
 *
 * @group tide_core
 */
class SystemInfoControllerTest extends KernelTestBase {

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'field', 'tide_core'];

  /**
   * The SystemInfoController instance.
   *
   * @var \Drupal\tide_core\Controller\SystemInfoController
   */
  protected $controller;

  /**
   * The virtual file system.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $vfsRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['field']);

    // Set up virtual file system.
    $this->vfsRoot = vfsStream::setup('root');
    vfsStream::newFile('composer.json')
      ->at($this->vfsRoot)
      ->setContent(json_encode([
        'require' => ['dpc-sdp/tide' => '1.2.3'],
        'extra' => ['sdp_version' => '4.5.6'],
      ]));

    $container = $this->container;

    // Mock the file system service.
    $file_system = $this->createMock('\Drupal\Core\File\FileSystemInterface');
    $file_system->method('realpath')
      ->willReturn($this->vfsRoot->url() . '/composer.json');
    $container->set('file_system', $file_system);

    $this->controller = SystemInfoController::create($container);
  }

  /**
   * Tests the getSystemInfo method.
   */
  public function testGetSystemInfo() {
    $response = $this->controller->getSystemInfo();
    $this->assertTrue($response->isOk());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertEquals('1.2.3', $content['tideVersion']);
    $this->assertEquals('4.5.6', $content['sdpVersion']);

    // Test caching.
    $cachedResponse = $this->controller->getSystemInfo();
    $this->assertEquals($response->getContent(), $cachedResponse->getContent());
  }

  /**
   * Tests the getFields method with valid input.
   */
  public function testGetFieldsValidInput() {
    $response = $this->controller->getFields('user');
    $this->assertTrue($response->isOk());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('user', $content);
    $this->assertArrayHasKey('user', $content['user']);

    // Test caching.
    $cachedResponse = $this->controller->getFields('user');
    $this->assertEquals($response->getContent(), $cachedResponse->getContent());
  }

  /**
   * Tests the getFields method with invalid input.
   */
  public function testGetFieldsInvalidInput() {
    $response = $this->controller->getFields('invalid_entity_type');
    $this->assertEquals(400, $response->getStatusCode());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertArrayHasKey('valid_types', $content);
  }

  /**
   * Tests the getFields method with 'all' input.
   */
  public function testGetFieldsAllInput() {
    $response = $this->controller->getFields('all');
    $this->assertTrue($response->isOk());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('user', $content);
  }

  /**
   * Tests error handling when composer.json is not found.
   */
  public function testComposerJsonNotFound() {
    // Remove the virtual composer.json file.
    unlink($this->vfsRoot->url() . '/composer.json');

    $response = $this->controller->getSystemInfo();
    $this->assertEquals(404, $response->getStatusCode());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
  }

  /**
   * Tests the getValidEntityTypes method indirectly.
   */
  public function testGetValidEntityTypes() {
    $response = $this->controller->getFields('all');
    $content = json_decode($response->getContent(), TRUE);

    // 'user' should always be a valid entity type
    $this->assertArrayHasKey('user', $content);

    // Test with an invalid entity type.
    $response = $this->controller->getFields('invalid_type');
    $content = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('valid_types', $content);
    $this->assertContains('user', $content['valid_types']);
  }

}
