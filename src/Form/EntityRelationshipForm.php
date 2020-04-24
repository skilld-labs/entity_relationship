<?php

namespace Drupal\entity_relationship_diagram\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a EntityRelationship form.
 */
class EntityRelationshipForm extends FormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a EntityRelationshipFormForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_relationship_diagram';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $key => $definition) {
      // Get only content entities for build diagram.
      if ($definition->get('group') == 'content') {
        $options[$key] = $definition->get('label')->getUntranslatedString();
      }
    }
    asort($options);
    $form['entities'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Which content entities include to diagram?'),
      '#multiple' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chosen_entities = [];
    foreach ($form_state->getValue('entities') as $entity => $value) {
      if ($value !== 0) {
        $chosen_entities[] = $entity;
      }
    }
    $form_state->setRedirect('entity_relationship_diagram.graph', ['entities' => $chosen_entities]);
  }

}
