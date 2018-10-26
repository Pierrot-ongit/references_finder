<?php

namespace Drupal\references_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Robo\Task\Npm\Base;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * FIXME.
 */
class ReferencesFinderForm extends FormBase {

  /**
   * The entity type Bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructeur.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Service state.
   */
  public function __construct(EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'references_finder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * @Todo : Dabord avec les nodes
     * Trouver tous les bundles de nodes
     * Lancer une boucle pour parcourir les champs du bundle choisit.
     * Trouver si le champ est une reference entities
     * Trouver le types d'entité rt les bundles autorisés.
     * Recommencer.
     *
     **/

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    $references = $this->findRefences('node', 'article');
    kint($references);

    if (!empty($bundles)) {
      $form['bundles'] = [
        '#type' => 'select',
        '#title' => $this->t('Choix du bundle'),
        '#options' => $bundles[0],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The configuration has been updated.'));
    die();
  }

  public function findRefences($entity_type, $bundle, $level = NULL) {
    if ($level === NULL) {
      $level = 1;
    }
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    $references[$level] = [
      'targets' => [],
    ];

    foreach ($fields  as $field) {
      if ($field instanceof BaseFieldDefinition == FALSE && $field->getType() === 'entity_reference' ) {
        $target_entity_type = str_replace('default:', '', $field->getSetting('handler'));
        $target_bundles = $field->getSetting('handler_settings')['target_bundles'];
        $references[$level]['targets'][] = [
          'target_entity_type' => $target_entity_type,
          'target_bundles' => array_values($target_bundles)
        ];
      }
    }

    // @Todo : Récursion à inclure. faire un &$references ?
    if (!empty($references[$level]['targets'])) {
      foreach ($references[$level]['targets'] as $target) {
        foreach ($target['target_bundles'] as $bundle) {
          $references[$level + 1][] = $this->findRefences($target['target_entity_type'], $bundle, $level +1);
        }
      }
    }
    return $references;
  }

}
