<?php

namespace Drupal\tide_search\EventSubscriber;

use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
use Drupal\elasticsearch_connector\Event\SupportsDataTypeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * ElasticSearch test event subscribers.
 */
class ElasticSearchEventSubscribers implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AlterSettingsEvent::class => 'onAlterSettingsEvent',
      FieldMappingEvent::class => 'onFieldMappingEvent',
      SupportsDataTypeEvent::class => 'onSupportsDataTypeEvent',
    ];
  }

  /**
   * Event handler for SupportsDataTypeEvent.
   */
  public function onSupportsDataTypeEvent(SupportsDataTypeEvent $event): void {
    if ($event->getType() === 'tide_search_text_field_with_keyword_and_prefix') {
      $event->setIsSupported(TRUE);
    }
  }

  /**
   * Event handler for FieldMappingEvent.
   */
  public function onFieldMappingEvent(FieldMappingEvent $event): void {
    $param = $event->getParam();
    $field = $event->getField();
    $type = $field->getType();
    $param = match ($type) {
      'tide_search_text_field_with_keyword_and_prefix' => [
        "type" => "text",
        "fields" => [
          "keyword" => [
            "type" => "keyword",
            "ignore_above" => 256,
          ],
          "prefix" => [
            "type" => "text",
            "index_options" => "docs",
            "analyzer" => "i_prefix",
            "search_analyzer" => "q_prefix",
          ],
        ],
      ],
      default => [],
    };
    $event->setParam($param);
  }

  /**
   * Event handler for AlterSettingsEvent.
   */
  public function onAlterSettingsEvent(AlterSettingsEvent $event): void {
    $settings = $event->getSettings();
    $settings['analysis'] = [
      "filter" => [
        "front_ngram" => [
          "type" => "edge_ngram",
          "min_gram" => "1",
          "max_gram" => "12",
        ],
      ],
      "analyzer" => [
        "i_prefix" => [
          "filter" => [
            "cjk_width",
            "lowercase",
            "asciifolding",
            "front_ngram",
          ],
          "tokenizer" => "standard",
        ],
        "q_prefix" => [
          "filter" => [
            "cjk_width",
            "lowercase",
            "asciifolding",
          ],
          "tokenizer" => "standard",
        ],
      ],
    ];
    $event->setSettings($settings);
  }

}
