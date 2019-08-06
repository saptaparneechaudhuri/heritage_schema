<?php

namespace Drupal\heritage_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Providess a form to add heritage text TOC.
 */
class AddHeritageTextTOC extends FormBase {

  /**
   * The entity type manager.
   *
   * @var entityTypeManager\Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  /**
   * The link generator service.
   *
   * @var pathLink\Drupal\Core\Utility\LinkGeneratorInterface
   */


  protected $pathLink;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $pathLink
   *   The service to provide link.
   * @param \Drupal\Core\Path\CurrentPathStack $currPath
   *   The service to provide current path.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LinkGeneratorInterface $pathLink, CurrentPathStack $currPath) {

    $this->entityTypeManager = $entityTypeManager;
    $this->pathLink = $pathLink;
    $this->currPath = $currPath;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('link_generator'),
      $container->get('path.current')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'heritage_schema_add_text_toc';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    // $node_info = Node::load($node);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_info = $node_storage->load($node);

    $form['textid'] = [
      '#type' => 'hidden',
      '#value' => $node,
    ];
    // If the TOC is already added show the info.
    if ($node_info->field_levels->value > 0) {
      $form['first_time'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
      $level_labels = explode(',', $node_info->field_level_labels->value);
      $topLevelTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = 0)", [':bundle' => $node_info->field_machine_name->value])->fetchAll();
      $topLevelTermsCount = count($topLevelTerms);
      if ($topLevelTermsCount == 0) {
        $topLevelTermsCount = '';
      }
      $form['textinfo'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Heritage Text Information'),
      ];
      $form['textinfo']['title'] = [
        '#type' => 'item',
         // '#markup' => $this->t('Heritage Text Name: ' . $node_info->title->value .'<small><i> (' . $node_info->field_machine_name->value . ')</i></small>'),
        '#markup' => $this->t('Heritage Text Name: @node_info_title <small><i> ( @node_info_machine_name ) </i></small>', ['@node_info_title' => $node_info->title->value, '@node_info_machine_name' => $node_info->field_machine_name->value]),
      ];
      $form['textinfo']['levels'] = [
        '#type' => 'item',
        // '#markup' => $this->t('No.of Levels: ' . $node_info->field_levels->value),
        '#markup' => $this->t('No.of Levels: @node_info', ['@node_info' => $node_info->field_levels->value]),
      ];
      $form['textinfo']['level_labels_info'] = [
        '#type' => 'item',
        // '#markup' => $this->t('Level Labels: ' . $node_info->field_level_labels->value),
        '#markup' => $this->t('Level Labels: @node_info', ['@node_info' => $node_info->field_level_labels->value]),
      ];
      $attributes = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ];

      // Get the text id from node.
      // Use dependency injection.
      $path = $this->currPath->getPath();
      // $path = \Drupal::request()->getpathInfo();
      $arg = explode('/', $path);

      // Text node id.
      $textId = $arg[2];
      $url = Url::fromRoute('node.add', ['node_type' => 'source_node']);
      $url->setOption('query', [
        'textid' => $textId,
      ]);
      // $source_node_link = \Drupal::l(t('Add a Source Node'), $url);
      $source_node_link = $this->pathLink->generate('Add a Source Node', $url);

      if ($topLevelTermsCount == 0) {
        $form['level1num'] = [
          '#type' => 'number',
          '#title' => $this->t("Enter total number of @level1", ['@level1' => $level_labels[0] . 's']),
          '#default_value' => $topLevelTermsCount,
        ];
      }
      if ($topLevelTermsCount > 0) {
        if (!empty($form_state->getTriggeringElement())) {
          $index = $topLevelTermsCount + 1;
          $term_name = $level_labels[0] . ' ' . $index;
          $result = add_term($index, $term_name, $node_info->field_machine_name->value);
          $topLevelTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = 0)", [':bundle' => $node_info->field_machine_name->value])->fetchAll();
          $topLevelTermsCount = count($topLevelTerms);
        }
        $form['textinfo']['addsource'] = [
          '#type' => 'item',
          '#markup' => $source_node_link,
        ];
        $form['textinfo']['editoc'] = [
          '#type' => 'button',
          '#value' => $this->t('Add a TOC Node'),
        ];
        $form['textinfo']['level1num'] = [
          '#type' => 'hidden',
          '#markup' => $topLevelTermsCount,
        ];
        $form['text_structure'][$level_labels[0]] = [
          '#type' => 'table',
          '#caption' => $this->t('Heritage Text Structure'),
          '#header' => [$level_labels[0], "Sub Levels", 'Actions'],
        ];
        for ($i = 0; $i < $topLevelTermsCount; $i++) {
          $levelNum = 0;
          $parent = $topLevelTerms[$i]->tid;
          $form['text_structure'][$level_labels[0]][$parent][$level_labels[0]]['#markup'] = $topLevelTerms[$i]->name;
          $form['text_structure'][$level_labels[0]][$parent][$level_labels[1]] = $this->getFormStructure($node_info->field_machine_name->value, $parent, $level_labels, $levelNum + 1, $form);
          $form['text_structure'][$level_labels[0]][$parent]['Actions'] = [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $topLevelTerms[$i]->tid]),
            '#attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
          ];
        }
      }
    }
    // If TOC is not added collect the basic info,
    // like machine name, level names etc..
    else {
      $num_levels = 0;
      $form['first_time'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];
      $form['machine_name'] = [
        '#type' => 'machine_name',
        '#maxlength' => 64,
        '#description' => $this->t('A unique name for this heritage text. It must only contain lowercase letters, numbers, and underscores.'),
        '#machine_name' => [
          'exists' => ['Drupal\node\Entity\NodeType', 'load'],
        ],
      ];
      $form['levels'] = [
        '#type' => 'select',
        '#title' => $this->t('No.of Levels for this text'),
        '#required' => TRUE,
        '#description' => $this->t('E.g. 2 levels for Gita (Chapter and Sloka), 3 levels for Valmiki Ramayana (Kanda, Sarga, Sloka) etc.'),
        '#options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'],
        '#default_value' => isset($form['levels']['#default_value']) ? $form['levels']['#default_value'] : NULL,
        '#ajax' => [
          'event' => 'change',
          'wrapper' => 'levels-labels',
          'callback' => '::ajaxLevelCallback',
        ],
      ];
      if (!empty($form_state->getTriggeringElement())) {
        $num_levels = $form_state->getTriggeringElement()['#value'];
      }
      $form['levels_labels'] = [
        '#type' => 'container',
        '#prefix' => '<div id="levels-labels">',
        '#suffix' => '</div>',
      ];
      if ($num_levels != 0) {
        $form['levels_labels']['levels_labels_fields'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Label for each level'),
          '#description' => $this->t('E.g. For Gita - Chapter and Sloka, for Valmiki Ramayana - Kanda, Sarga, Sloka etc.'),
        ];
        for ($i = 1; $i <= $num_levels; $i++) {
          $form['levels_labels']['levels_labels_fields'][$i] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label for level @i', ['@i' => $i]),
            '#size' => 40,
            '#maxlength' => 128,
            '#required' => TRUE,
          ];
        }
      }
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save TOC'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Stores newly added text schema into the database table,.
   *
   * `heritage_text_structure,and creates the content types and vocabulary.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $textid = $form_state->getValue('textid');
    $first_time = $form_state->getValue('first_time');
    // $node = Node::load($textid);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($textid);

    if ($first_time == 1) {
      $title = db_query("SELECT title FROM `node_field_data` WHERE nid = :textid", [':textid' => $textid])->fetchField();
      $machineName = $form_state->getValue('machine_name');
      $levels = $form_state->getValue('levels');
      for ($i = 1; $i <= $levels; $i++) {
        if ($i == 1) {
          $level_labels = $form_state->getValue($i);
        }
        else {
          $level_labels = $level_labels . ',' . $form_state->getValue($i);
        }
      }
      $node->field_machine_name->value = $machineName;
      $node->field_levels->value = $levels;
      $node->field_level_labels->value = $level_labels;
      $node->save();
      $content_type = create_content_type($title, $machineName);
      $vocabulary_status = add_vocabulary($title, $machineName);
      $labels = ['Positional Index', 'Original Content'];
      $machine_names = ['field_positional_index', 'field_original_content'];
      $types = ['entity_reference', 'boolean'];
      // No need to select format here because format of type,
      // Audio,video etc. is handled by source nodes.
      // Also types are not set as files here.
      $format_tmp_array = [];
      $cardinality = [-1];
      $field_status = add_other_fields($content_type->id(), 'node', $labels, $machine_names, $types, $format_tmp_array, $cardinality);
      drupal_set_message($this->t("Heritage Text Schema stored successfully. Add more inforation on the TOC structure"));
    }
    elseif ($first_time == 0) {
      $vid = $node->field_machine_name->value;
      $level1num = $form_state->getValue('level1num');
      $level_labels = explode(',', $node->field_level_labels->value);
      $topLevelTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = 0)", [':bundle' => $vid])->fetchAll();
      if (count($topLevelTerms) == 0) {
        $parent = NULL;
        $this->createTermsBatch($level_labels[0], $level1num, $vid, $parent);
      }
      else {
        $numLevels = $form_state->getValue($level_labels[0]);
        $parents = array_keys($numLevels);
        for ($i = 0; $i < count($parents); $i++) {
          $getTermDetails = $this->getDetails($numLevels[$parents[$i]], $level_labels, 1, $parents[$i]);
          // print("<pre>");print_r($getTermDetails);exit;
          for ($j = 0; $j < count($getTermDetails); $j++) {
            $this->createTermsBatch($getTermDetails[$j]['label'], $getTermDetails[$j]['value'], $vid, $getTermDetails[$j]['parent']);
          }
        }
      }
    }
  }

  /**
   * Ajax callback function.
   */
  public function ajaxLevelCallback(array $form, FormStateInterface $form_state) {
    return $form['levels_labels'];
  }

  /**
   * Custom function to create the form element dynamically.
   *
   * @param string $vid
   *   Vocabulary Name.
   * @param int $parent
   *   Taxonomy Id of the parent term.
   * @param string $level_labels
   *   Names of the levels in the heritage text.
   * @param int $currentLevel
   *   Index of the current level.
   * @param object $form
   *   The form used.
   */
  public function getFormStructure($vid, $parent, $level_labels, $currentLevel, $form) {
    $childTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = :parent)", [':bundle' => $vid, ':parent' => $parent])->fetchAll();
    if (count($childTerms) > 0) {
      if ($currentLevel + 1 < count($level_labels)) {
        $headers = [$level_labels[$currentLevel], 'Sub Levels', 'Actions'];
      }
      else {
        $headers = [$level_labels[$currentLevel], 'Actions'];
      }
      $form['text_structure'][$level_labels[$currentLevel]]['fieldset'] = [
        '#type' => 'details',
        '#title' => $this->t('View Sub Levels'),
        '#open' => FALSE,
      ];
      $form['text_structure'][$level_labels[$currentLevel]]['fieldset']['table'] = [
        '#type' => 'table',
        '#header' => $headers,
      ];
      for ($i = 0; $i < count($childTerms); $i++) {
        $parent = $childTerms[$i]->tid;
        $form['text_structure'][$level_labels[$currentLevel]]['fieldset']['table'][$parent][$level_labels[$currentLevel]]['#markup'] = $childTerms[$i]->name;
        if ($currentLevel + 1 < count($level_labels)) {
          $form['text_structure'][$level_labels[$currentLevel]]['fieldset']['table'][$parent][$level_labels[$currentLevel + 1]] = $this->getFormStructure($vid, $parent, $level_labels, $currentLevel + 1, $form);
        }
        $form['text_structure'][$level_labels[$currentLevel]]['fieldset']['table'][$parent]['Actions'] = [
          '#type' => 'link',
          '#title' => $this->t('Edit'),
          '#url' => Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $childTerms[$i]->tid]),
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ];
      }
      return $form['text_structure'][$level_labels[$currentLevel]];
    }
    else {
      $form['text_structure'][$level_labels[$currentLevel - 1]][$parent][$level_labels[$currentLevel]] = [
        '#type' => 'number',
        '#required' => TRUE,
        '#title' => $this->t("Enter total number of @level1", ['@level1' => $level_labels[$currentLevel] . 's']),
      ];
      return $form['text_structure'][$level_labels[$currentLevel - 1]][$parent][$level_labels[$currentLevel]];
    }
  }

  /**
   * Start batch operation for creating the taxonomy term.
   *
   * @param string $label
   *   Name of the level.
   * @param int $numTerms
   *   Total no.of terms to be created.
   * @param string $vid
   *   Vocabulary in which the terms should be created.
   * @param int $parent
   *   Taxonomy Id of the parent term.
   */
  public function createTermsBatch($label, $numTerms, $vid, $parent = NULL) {
    $batch = [];
    $operations = [];
    for ($i = 1; $i <= $numTerms; $i++) {
      $term_name = $label . ' ' . $i;
      // Each operation is an array consisting of
      // - The function to call.
      // - An array of arguments to that function.
      $operations[] = [
        'create_taxonomy_terms_batch',
      [
        $i, $vid, $term_name, $parent,
        $this->t('(Operation @operation)', ['@operation' => $i]),
      ],
      ];
    }
    $batch = [
      'title' => $this->t('Creating TOC Structure of @num operations', ['@num' => $numTerms]),
      'operations' => $operations,
      'finished' => 'create_taxonomy_terms_batch_finished',
    ];
    batch_set($batch);
    return TRUE;
  }

  /**
   * Custom function to get the label and value from the form.
   *
   * @param array $levels
   *   An array of the form values.
   * @param string $label_levels
   *   Name of the level labels.
   * @param int $parent
   *   Parent id of the term.
   * @param array $result
   *   An array containing parent, label and value.
   * @param int $currentDepth
   *   Depth of the array.
   */
  public function getDetails(array $levels, $label_levels, $currentDepth = 1, $parent, array $result = []) {
    $index = count($result);
    $parents = array_keys($levels);
    for ($j = 0; $j < count($parents); $j++) {
      if (is_array($levels[$parents[$j]])) {
        $result = $this->getDetails($levels[$parents[$j]], $label_levels, $currentDepth + 1, $parents[$j], $result);
      }
      else {
        if (isset($levels[$parents[$j]])) {
          $result[$index]['parent'] = $parent;
          $result[$index]['label'] = $parents[$j];
          $result[$index]['value'] = $levels[$parents[$j]];
        }
        return $result;
      }
    }
    return $result;
  }

}
