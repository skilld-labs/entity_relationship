<?php

namespace Drupal\entity_relationship_diagram\Commands;

use Drush\Commands\DrushCommands as DrushCommandsBase;
use Drupal\entity_relationship_diagram\Diagram;

/**
 * Defines Drush commands for the entity_relationship_diagram module.
 */
class DrushCommands extends DrushCommandsBase {

  /**
   * @var \Drupal\entity_relationship_diagram\Diagram
   */
  protected $diagram;

  /**
   * Constructor method.
   *
   * @param \Drupal\entity_relationship\Diagram $diagram
   *   Class to create relationships diagrams.
   */
  public function __construct(Diagram $diagram) {
    $this->diagram = $diagram;
  }

  /**
   * Entity relations diagram.
   *
   * @command entity_relationship_diagram:diagram
   *
   * @option entity_type
   *   One or several entity types, separated by comma. Skipping this argument will output all content entities.
   *
   * @usage drush entity_relationship_diagram:diagram --entity_type=xxx,xxx,xxx | dot -Gratio=0.7 -Eminlen=2 -T png -o ./output.png
   *   Output diagram for the specified entity types. Packages "graphviz" and "ttf-freefont" must be installed on your system.
   *
   * @aliases erdia
   */
  public function createDiagram(array $options = ['entity_type' => NULL]) {
    if (isset($options['entity_type'])) {
      $entity_types = array_filter(explode(',', preg_replace('/\s/', '', $options['entity_type'])));
    }
    else {
      $entity_types = array_keys(self::getContentEntitiesList());
    }
    $this->diagram->create($entity_types);
    $this->output()->writeln($this->diagram->generateGraph());
  }

  /**
   * Available entities.
   *
   * @command entity_relationship_diagram:show_entities
   *
   * @usage drush entity_relationship_diagram:show_entities
   *   Output a list of available entities.
   *
   * @aliases ersen
   */
  public function showEntities(array $options = []) {
    $available_entities = self::getContentEntitiesList();
    $this->output()->writeln(print_r($available_entities));
  }

  /**
   * Get a list of available content entities.
   */
  public static function getContentEntitiesList() {
    $entity_types = [];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $key => $definition) {
      if ($definition->get('group') == 'content') {
        $entity_types[$key] = $definition->get('label')->getUntranslatedString();
      }
    }
    asort($entity_types);

    return $entity_types;
  }

}
