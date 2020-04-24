<?php

namespace Drupal\entity_relationship_diagram\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_relationship_diagram\Diagram;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the report page.
 */
class DiagramController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\entity_relationship\Diagram
   */
  protected $diagram;

  /**
   * ReportController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\entity_relationship\Diagram $diagram
   *   Class for create relationships diagram.
   */
  public function __construct(RequestStack $request_stack, Diagram $diagram) {
    $this->requestStack = $request_stack;
    $this->diagram = $diagram;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_relationship_diagram.diagram')
    );
  }

  /**
   * Main method to create full graph of entities.
   *
   * @return array
   *   A render array with a generated diagram.
   */
  public function content() {
    $build = [];
    $request = $this->requestStack->getCurrentRequest();
    $entity_types = $request->query->get('entities');

    $this->diagram->create($entity_types);
    $graph_string = $this->diagram->generateGraph();

    $build['#attached']['library'][] = 'entity_relationship_diagram/entity_relationship_diagram';
    $build['#attached']['drupalSettings']['entity_relationship_diagram']['dataSVG'] = $graph_string;

    return $build;
  }

}
