<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_data_pipeline\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\data_pipelines\Entity\Dataset;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi\Controller\EntryPoint;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\tide_data_pipeline\ResourceType\DataPipelinesResourceTypeRepository;
use Drupal\tide_data_pipeline\Routing\DataPipelinesRouteSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the JSON:API output of Data Pipeline resource types.
 */
#[CoversClass(DataPipelinesResourceTypeRepository::class)]
#[CoversClass(DataPipelinesRouteSubscriber::class)]
#[Group('tide_data_pipeline')]
class DataPipelinesResourceTypeRepositoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'data_pipelines',
    'data_pipelines_test',
    'entity',
    'entity_reference_revisions',
    'field',
    'file',
    'jsonapi',
    'jsonapi_extras',
    'key',
    'link',
    'options',
    'paragraphs',
    'serialization',
    'system',
    'tide_data_pipeline',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('data_pipelines');
    $this->installEntitySchema('file');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('user');

    ParagraphsType::create([
      'id' => 'self_service_map',
      'label' => 'Self service map',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_select_dataset',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'data_pipelines'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_select_dataset',
      'entity_type' => 'paragraph',
      'bundle' => 'self_service_map',
    ])->save();
    JsonapiResourceConfig::create([
      'id' => 'paragraph--self_service_map',
      'disabled' => FALSE,
      'path' => 'paragraph/self_service_map',
      'resourceType' => 'paragraph--self_service_map',
      'resourceFields' => [
        'field_select_dataset' => [
          'disabled' => FALSE,
          'fieldName' => 'field_select_dataset',
          'publicName' => 'field_select_dataset',
          'enhancer' => ['id' => 'dataset_field_enhancer'],
        ],
      ],
    ])->save();
  }

  /**
   * Tests Data Pipeline resource types have no routes but are not internal.
   */
  public function testResourceType(): void {
    $repository = $this->container->get('jsonapi.resource_type.repository');
    $this->assertInstanceOf(DataPipelinesResourceTypeRepository::class, $repository);

    foreach (['csv:file', 'json:file'] as $bundle) {
      $resource_type = $repository->get('data_pipelines', $bundle);
      $this->assertFalse($resource_type->isInternal());
      $this->assertFalse($resource_type->isLocatable());
      $this->assertFalse($resource_type->isMutable());
    }

    $paragraph_type = $repository->get('paragraph', 'self_service_map');
    $this->assertTrue($paragraph_type->isLocatable());
    $this->assertTrue($paragraph_type->isMutable());

    $this->container->get('router.builder')->rebuild();
    $route_names = array_keys(iterator_to_array($this->container->get('router.route_provider')->getAllRoutes()));
    $this->assertContains('jsonapi.paragraph--self_service_map.individual', $route_names);
    // Only the inaccessible route that access denied errors link to exists.
    $data_pipeline_routes = preg_grep('/^jsonapi\.data_pipelines--/', $route_names);
    $this->assertContains('jsonapi.data_pipelines--csv:file.individual', $data_pipeline_routes);
    $this->assertSame([], preg_grep('/\.individual$/', $data_pipeline_routes, PREG_GREP_INVERT));
    $route = $this->container->get('router.route_provider')->getRouteByName('jsonapi.data_pipelines--csv:file.individual');
    $this->assertSame('FALSE', $route->getRequirement('_access'));
  }

  /**
   * Tests the JSON:API entry point has no invalid link keys.
   *
   * LinkCollection asserts its keys are valid, so this fails on the first
   * invalid key when assertions are enabled.
   */
  public function testEntryPoint(): void {
    $this->container->get('router.builder')->rebuild();
    $links = EntryPoint::create($this->container)->index()->getResponseData()->getLinks();

    $keys = array_keys(iterator_to_array($links));
    $this->assertContains('paragraph--self_service_map', $keys);
    $this->assertSame([], preg_grep('/:|^data_pipelines--/', $keys));
  }

  /**
   * Tests a reference to a deleted dataset is reported as missing.
   */
  public function testDeletedDataset(): void {
    $dataset = Dataset::create([
      'source' => 'csv:file',
      'name' => 'Test dataset',
      'machine_name' => 'test_dataset',
      'pipeline' => 'pipeline_with_no_validation',
    ]);
    $dataset->save();
    $paragraph = Paragraph::create([
      'type' => 'self_service_map',
      'field_select_dataset' => $dataset,
    ]);
    $paragraph->save();
    $dataset->delete();

    $this->container->get('entity_type.manager')->getStorage('paragraph')->resetCache();
    $paragraph = Paragraph::load($paragraph->id());
    $identifiers = ResourceIdentifier::toResourceIdentifiers($paragraph->get('field_select_dataset'));
    $this->assertCount(1, $identifiers);

    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('paragraph', 'self_service_map');
    $normalization = $this->container->get('jsonapi.serializer')->normalize($identifiers[0], 'api_json', [
      'account' => new AnonymousUserSession(),
      'resource_object' => ResourceObject::createFromEntity($resource_type, $paragraph),
    ])->getNormalization();
    $this->assertSame('missing', $normalization['id']);
    $this->assertArrayNotHasKey('index_name', $normalization['meta']);
  }

  /**
   * Tests an access denied error for a dataset can be normalized.
   */
  public function testAccessDeniedError(): void {
    $this->container->get('router.builder')->rebuild();
    $dataset = Dataset::create([
      'source' => 'csv:file',
      'name' => 'Test dataset',
      'machine_name' => 'test_dataset',
      'pipeline' => 'pipeline_with_no_validation',
    ]);
    $dataset->save();

    $exception = new EntityAccessDeniedHttpException($dataset, AccessResult::forbidden(), '/data', 'Denied.');
    $errors = $this->container->get('jsonapi.serializer')
      ->normalize($exception, 'api_json', ['account' => new AnonymousUserSession()])
      ->getNormalization();

    $this->assertSame('403', $errors[0]['status']);
    $this->assertStringContainsString($dataset->uuid(), $errors[0]['links']['via']['href']);
  }

  /**
   * Tests the update hook applies the dataset field enhancer.
   */
  public function testUpdateAppliesEnhancer(): void {
    $resource_config = JsonapiResourceConfig::load('paragraph--self_service_map');
    $resource_fields = $resource_config->get('resourceFields');
    $resource_fields['field_select_dataset']['enhancer']['id'] = '';
    $resource_config->set('resourceFields', $resource_fields)->save();

    $this->container->get('module_handler')->loadInclude('tide_data_pipeline', 'install');
    tide_data_pipeline_update_11001();

    $resource_fields = JsonapiResourceConfig::load('paragraph--self_service_map')->get('resourceFields');
    $this->assertSame('dataset_field_enhancer', $resource_fields['field_select_dataset']['enhancer']['id']);
    $this->assertFalse($resource_fields['field_select_dataset']['disabled']);
  }

  /**
   * Tests the relationship exposes the metadata of the selected dataset.
   */
  public function testRelationship(): void {
    $dataset = Dataset::create([
      'source' => 'csv:file',
      'name' => 'Test dataset',
      'machine_name' => 'test_dataset',
      'pipeline' => 'pipeline_with_no_validation',
    ]);
    $dataset->save();
    $paragraph = Paragraph::create([
      'type' => 'self_service_map',
      'field_select_dataset' => $dataset,
    ]);
    $paragraph->save();

    $identifiers = ResourceIdentifier::toResourceIdentifiers($paragraph->get('field_select_dataset'));
    $this->assertCount(1, $identifiers);

    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('paragraph', 'self_service_map');
    $normalization = $this->container->get('jsonapi.serializer')->normalize($identifiers[0], 'api_json', [
      'account' => new AnonymousUserSession(),
      'resource_object' => ResourceObject::createFromEntity($resource_type, $paragraph),
    ])->getNormalization();

    $this->assertSame('data_pipelines--csv:file', $normalization['type']);
    $this->assertSame($dataset->uuid(), $normalization['id']);
    $this->assertSame('test_dataset', $normalization['meta']['index_name']);
    $this->assertSame('pipeline_with_no_validation', $normalization['meta']['pipeline']);
  }

}
