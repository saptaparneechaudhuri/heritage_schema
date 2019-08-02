<?php

namespace Drupal\heritage_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Providess a form to add a TOC node.
 */
class AddTOCNode extends FormBase {

  /**
   * The entity type manager.
   *
   * @var entityTypeManager\Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {

    $this->entityTypeManager = $entityTypeManager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'heritage_schema_edit_text_toc';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    // $node_info = Node::load($node);
    // Using dependency injection for node
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_info = $node_storage->load($node);

    $machine_name = $node_info->field_machine_name->value;
    $level_labels = explode(',', $node_info->field_level_labels->value);

    $form['textid'] = [
      '#type' => 'hidden',
      '#value' => $node,
    ];

    // Get the number of top level terms already added.
    $topLevelTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = 0)", [':bundle' => $machine_name])->fetchAll();
    $topLevelTermsCount = count($topLevelTerms);

    $form['tocname'] = [
      '#type' => 'item',
      '#markup' => $this->t("@level1 @count", ['@level1' => $level_labels[0], '@count' => $topLevelTermsCount + 1]),
    ];

    $form['tocname'] = [
      '#type' => 'item',
      '#markup' => $this->t("@level1 @count", ['@level1' => $level_labels[0], '@count' => $topLevelTermsCount + 1]),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save TOC Node Structure'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Stores newly added text schema into the database table,.
   *
   * `heritage_text_structure` and.
   *
   * creates the content types and vocabulary.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
