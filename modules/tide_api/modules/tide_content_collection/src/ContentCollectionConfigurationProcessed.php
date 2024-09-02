<?php

namespace Drupal\tide_content_collection;

use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for Content Collection Configuration.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the YAML configuration.
 */
class ContentCollectionConfigurationProcessed extends TypedData {

  /**
   * The configuration.
   *
   * @var array|null
   */
  protected $configuration = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('text source')) {
      throw new \InvalidArgumentException("The definition's 'text source' key has to specify the name of the text property holding the YAML configuration.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->configuration !== NULL) {
      return $this->configuration;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $text = $item->{($this->definition->getSetting('text source'))};

    // Avoid doing unnecessary work on empty strings.
    if (!isset($text) || empty($text) || trim($text) === '') {
      $this->configuration = [];
    }
    else {
      $this->configuration = Json::decode($text);
    }

    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->configuration = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
