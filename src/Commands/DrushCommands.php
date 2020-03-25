<?php

namespace Drupal\entity_relationship\Commands;

use Drush\Commands\DrushCommands as DrushCommandsBase;
use Drupal\entity_relationship\Diagram;

/**
 * Defines Drush commands for the entity_relationship module.
 */
class DrushCommands extends DrushCommandsBase {

  /**
   * @var \Drupal\entity_relationship\Diagram
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
   * @command entity_relationship:diagram
   *
   * @param entity_type
   *   One or several entity types, separated by comma.
   *
   * @usage drush entity_relationship:diagram xxx,xxx,xxx | dot -Gratio=0.7 -Eminlen=2 -T png -o ./output.png
   *   Output diagram for the specified entity types. Packages "graphviz" and "ttf-freefont" must be installed on your system.
   *
   * @aliases erdia
   */
  public function createDiagram($entity_type, array $options = []) {
    $this->diagram->create(array_filter(explode(',', preg_replace('/\s/', '', $entity_type))));
    $this->output()->writeln($this->diagram->generateGraph());
  }

}
