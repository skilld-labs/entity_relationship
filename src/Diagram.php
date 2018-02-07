<?php

namespace Drupal\entity_relationship;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class for create diagram from drupal entities.
 */
class Diagram {

  /**
   * Variable with graph data.
   *
   * @var array
   */
  protected $graph;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Diagram constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets the entity relationship graph.
   *
   * @return array|null
   *   Graph data.
   */
  public function getGraph() {
    return $this->graph;
  }

  /**
   * Create full graph of entities.
   *
   * @param string|null $entity_types
   *   Pass on and entity type to render only.
   */
  public function create($entity_types) {
    $this->graph = [];
    $entities = [];
    $entity_refs = [];
    $this->entityReferenceConnections($entity_refs);

    if ($entity_types) {
      foreach ($entity_types as $item) {
        $entities[$item] = $this->entityTypeManager->getDefinition($item);
      }
    }

    foreach ($entities as $entity_type => $entity) {
      if (!($entity instanceof ContentEntityType)) {
        continue;
      }

      $entity_info['title'] = $entity->getLabel();

      $base_table = $entity->getBaseTable();
      if (!empty($base_table)) {
        $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);
        if (!empty($base_field_definitions)) {
          foreach ($base_field_definitions as $field_name => $field_info) {
            $entity_info['properties'][$field_name] = [
              'type' => $field_info->getDataType(),
            ];
          }
        }

        // Generate the bundles.
        $bundle_entity_name = $entity->getBundleEntityType();
        if (!empty($bundle_entity_name)) {
          $bundles = $this->entityTypeManager->getStorage($bundle_entity_name)->loadMultiple();
          $bundles = array_map(function (EntityInterface $bundle_entity) {
            return $bundle_entity->label();
          }, $bundles);
        }
        elseif ($entity->hasHandlerClass('bundle_plugin')) {
          $bundle_handler = $this->entityTypeManager->getHandler($entity->id(), 'bundle_plugin');
          $bundles = array_map(function (array $definition) {
            return $definition['label'];
          }, $bundle_handler->getBundleInfo());
        }
        else {
          $bundles = [
            $entity->id() => $entity->getLabel(),
          ];
        }

        if (!empty($bundles)) {
          foreach ($bundles as $bundle_name => $bundle_label) {
            $this->graph['entities']['cluster_entity_group_' . $entity_type]['entity_' . $entity_type . '__bundle_' . str_replace('-', '_', $bundle_name)] = [
              'title' => $bundle_label,
            ];
          }
          foreach ($bundles as $bundle_name => $bundle_label) {
            $instances = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle_name);
            foreach ($instances as $field_name => $instance_info) {
              $field_property_info = [
                'label' => $field_name,
                'type' => $instance_info->getType(),
              ];

              if ($instance_info->getFieldStorageDefinition()->getCardinality() != 1) {
                $field_property_info['type'] = 'list<' . $field_property_info['type'] . '>';
              }

              $this->graph['entities']['cluster_entity_group_' . $entity_type]['entity_' . $entity_type . '__bundle_' . $bundle_name]['fields'][$field_name] = $field_property_info;
            }
          }
        }
        $group = &$this->graph['entities']['cluster_entity_group_' . $entity_type];
        $group['label'] = $entity->getLabel();
        $group['group'] = TRUE;
      }

      // Entity reference edges.
      if (isset($entity_refs[$entity_type])) {
        foreach ($entity_refs[$entity_type] as $bundle_name => $field_ref_info) {
          foreach ($field_ref_info as $field_name => $target) {
            foreach ($target as $target_type => $target_info) {
              $relationship = 'entity_' . $target_type . '__bundle_' . $target_info['bundle'];
              $this->setRelationship('entity_' . $entity_type . '__bundle_' . $bundle_name, $relationship, $target_info['required'], $target_info['cardinality'], $target_info['fieldname']);
            }
          }
        }
      }
    }
  }

  /**
   * Get entity reference connections.
   *
   * @param array $field_relation
   *   Field relationship data.
   */
  private function entityReferenceConnections(array &$field_relation) {
    $entity_reference_map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');

    foreach ($entity_reference_map as $type_referencing => $entity_reference_fields) {
      foreach ($entity_reference_fields as $field_name => $map_info) {
        $field_storage_info = FieldStorageConfig::loadByName($type_referencing, $field_name);
        if (empty($field_storage_info)) {
          continue;
        }

        $bundle_referencing = reset($map_info['bundles']);
        $type_referenced = $field_storage_info->getSetting('target_type');

        $field_info = FieldConfig::loadByName($type_referencing, $bundle_referencing, $field_name);
        if (!empty($field_info->getSetting('handler_settings'))) {
          if (isset($field_info->getSetting('handler_settings')['target_bundles'])) {
            $target_bundles = $field_info->getSetting('handler_settings')['target_bundles'];
            foreach ($target_bundles as $target_bundle) {
              $field_relation[$type_referencing][$bundle_referencing][$field_name][$type_referenced] = [
                'bundle' => $target_bundle,
                'cardinality' => $field_storage_info->getCardinality(),
                'required' => $field_info->isRequired(),
                'fieldname' => $field_name,
              ];
            }
          }
        }
      }
    }
  }

  /**
   * Create an Edge connection.
   *
   * @param string $source
   *   Edge source.
   * @param string $target
   *   Edge target.
   * @param bool $required
   *   Required or not.
   * @param int $cardinality
   *   Relationship cardinality.
   * @param string $fieldname
   *   Field name.
   */
  private function setRelationship($source, $target, $required, $cardinality, $fieldname) {
    $edge_info = [
      'arrowhead' => 'normal',
    ];
    if ($cardinality >= 1) {
      $min_cardinality = $required ? $cardinality : 0;
      $max_cardinality = $cardinality;
    }
    else {
      $min_cardinality = $required ? 1 : 0;
      $max_cardinality = '*';
    }
    $edge_info['taillabel'] = $min_cardinality . '..' . $max_cardinality;

    $edge_info['headlabel'] = '1..*';
    $edge_info['fieldname'] = $fieldname;

    $this->graph['edges'][$source][$target] = $edge_info;
  }

  /**
   * Render graph into digraph format.
   *
   * @return string
   *   Final graph for render.
   */
  public function generateGraph() {
    // Merge in defaults.
    $this->graph += [
      'entities' => [],
      'edges' => [],
    ];
    $output = "digraph G {\n";

    $output .= "node [\n";
    $output .= "shape = \"record\"\n";
    $output .= "]\n";

    foreach ($this->graph['entities'] as $name => $entity_info) {
      if (!empty($entity_info['group'])) {
        $output .= $this->drawSubgraph($name, $entity_info);
      }
      else {
        $output .= $this->drawEntity($name, $entity_info);
      }
    }

    foreach ($this->graph['edges'] as $source_entity => $edges) {
      foreach ($edges as $target_entity => $edge_info) {
        $output .= "edge [\n";
        foreach ($edge_info as $k => $v) {
          $output .= ' "' . Html::escape($k) . '" = "' . Html::escape($v) . '"' . "\n";
        }
        $output .= "]\n";
        $color = $this->randomColor();
        $output .= new FormattableMarkup('@source_entity -> @target_entity [color="@color"][label="(@fieldname)" fontcolor="@color"]', [
          '@source_entity' => $source_entity,
          '@target_entity' => $target_entity,
          '@color' => $color,
          '@fieldname' => $edge_info['fieldname'],
        ]);
      }
    }

    $output .= "\n}\n";

    return $output;
  }

  /**
   * Create random RGB color.
   *
   * @return string
   *   Hex Color string.
   */
  private function randomColor() {
    $color = '#';
    for ($i = 0; $i < 3; $i++) {
      $color .= str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }
    return $color;
  }

  /**
   * Draw a subgraph.
   *
   * @param string $name
   *   Name.
   * @param array $subgraph_info
   *   Subgraph info.
   *
   * @return string
   *   Graph expression.
   */
  private function drawSubgraph($name, array $subgraph_info) {
    $label = $subgraph_info['label'];
    unset($subgraph_info['label']);
    unset($subgraph_info['group']);

    $output = "subgraph $name {\n";
    $output .= 'label = "' . Html::escape($label) . '"' . "\n";

    foreach ($subgraph_info as $entity_name => $entity_info) {
      $output .= $this->drawEntity($entity_name, $entity_info);
    }

    $output .= "}\n";
    return $output;
  }

  /**
   * Draw a entity.
   *
   * @param string $name
   *   Name.
   * @param array $entity_info
   *   Entity info.
   *
   * @return string
   *   entity box expression.
   */
  private function drawEntity($name, array $entity_info) {
    // Merge in defaults.
    $entity_info += [
      'title' => $name,
      'properties' => [],
      'fields' => [],
      'methods' => [],
    ];

    $label = $entity_info['title'] . '|';

    foreach ($entity_info['properties'] as $property_name => $property_info) {
      $property_type = !empty($property_info['type']) ? $property_info['type'] : '';
      $label .= $property_name . ' : ' . $property_type . '\l';
    }

    $label .= '|';

    foreach ($entity_info['fields'] as $field_name => $field_info) {
      $field_type = !empty($field_info['type']) ? $field_info['type'] : '';
      $label .= $field_name . ' : ' . $field_type . '|';
    }

    return $name . ' [ label = "{' . Html::escape($label) . '}" ]';
  }

}
