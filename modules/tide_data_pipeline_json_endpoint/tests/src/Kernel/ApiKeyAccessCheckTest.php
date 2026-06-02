<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_data_pipeline_json_endpoint\Kernel;

use DG\BypassFinals;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tide_data_pipeline_json_endpoint\Access\ApiKeyAccessCheck;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the ApiKeyAccessCheck service.
 *
 * @group tide_data_pipeline_json_endpoint
 *
 * @covers \Drupal\tide_data_pipeline_json_endpoint\Access\ApiKeyAccessCheck
 */
class ApiKeyAccessCheckTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'options',
    'link',
    'file',
    'entity',
    'data_pipelines',
    'tide_data_pipeline_json_endpoint',
    'user',
    'system',
  ];

  protected Route $route;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['tide_data_pipeline_json_endpoint']);
    $this->route = new Route('/api/datasets/{machine_name}/push', requirements: ['_data_pipeline_api_key' => 'TRUE']);
    BypassFinals::enable(FALSE);
  }

  /**
   * Returns the access check instance from the container.
   */
  private function check(): ApiKeyAccessCheck {
    return $this->container->get('tide_data_pipeline_json_endpoint.api_key_access_check');
  }

  /**
   * Returns a POST request optionally carrying an API key header.
   */
  private function request(?string $api_key = NULL): Request {
    $request = Request::create('/api/datasets/test/push', 'POST');
    if ($api_key !== NULL) {
      $request->headers->set('X-Api-Key', $api_key);
    }
    return $request;
  }

  /**
   * Tests that applies() returns true for routes with _data_pipeline_api_key.
   */
  public function testAppliesReturnsTrueForTaggedRoute(): void {
    $this->assertTrue($this->check()->applies($this->route));
  }

  /**
   * Tests that applies() returns false for routes without _data_pipeline_api_key.
   */
  public function testAppliesReturnsFalseForUntaggedRoute(): void {
    $this->assertFalse($this->check()->applies(new Route('/some/other/route')));
  }

  /**
   * Tests that the correct key grants access.
   */
  public function testAccessAllowedWithCorrectKey(): void {
    putenv('DATA_PIPELINE_PUSH_API_KEY=super-secret-test-key');

    $result = $this->check()->access($this->route, $this->request('super-secret-test-key'));

    $this->assertTrue($result->isAllowed());

    putenv('DATA_PIPELINE_PUSH_API_KEY=');
  }

  /**
   * Tests that a missing X-Api-Key header is forbidden.
   */
  public function testAccessForbiddenWhenHeaderMissing(): void {
    putenv('DATA_PIPELINE_PUSH_API_KEY=super-secret-test-key');

    $result = $this->check()->access($this->route, $this->request());

    $this->assertTrue($result->isForbidden());

    putenv('DATA_PIPELINE_PUSH_API_KEY=');
  }

  /**
   * Tests that an incorrect API key is forbidden.
   */
  public function testAccessForbiddenWithWrongKey(): void {
    putenv('DATA_PIPELINE_PUSH_API_KEY=super-secret-test-key');

    $result = $this->check()->access($this->route, $this->request('wrong-key'));

    $this->assertTrue($result->isForbidden());

    putenv('DATA_PIPELINE_PUSH_API_KEY=');
  }

  /**
   * Tests that access is forbidden when the env variable is not set.
   */
  public function testAccessForbiddenWhenEnvVariableNotSet(): void {
    putenv('DATA_PIPELINE_PUSH_API_KEY=');

    $result = $this->check()->access($this->route, $this->request('any-key'));

    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that the result is never cached (access depends on the request header).
   */
  public function testAccessResultIsNeverCached(): void {
    putenv('DATA_PIPELINE_PUSH_API_KEY=my-key');

    $allowed = $this->check()->access($this->route, $this->request('my-key'));
    $forbidden = $this->check()->access($this->route, $this->request('bad-key'));

    $this->assertSame(0, $allowed->getCacheMaxAge());
    $this->assertSame(0, $forbidden->getCacheMaxAge());

    putenv('DATA_PIPELINE_PUSH_API_KEY=');
  }

}
