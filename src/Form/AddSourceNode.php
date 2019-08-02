<?php

namespace Drupal\heritage_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Providess a form to add a source node.
 */
class AddSourceNode extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'heritage_schema_add_source_node';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form['textid'] = [
      '#type' => 'hidden',
      '#value' => $node,
    ];
    $num_levels = 0;
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the Source Text'),
      '#description' => $this->t('Eg: Hindi Translation By Swami Ramsukhdas.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#required' => TRUE,
      '#options' => [
        'English' => 'English',
        'Hindi' => 'Hindi',
        'Sanskrit' => 'Sanskrit',
      ],
    ];
    $form['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author'),
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['format'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Format'),
      '#required' => TRUE,
      '#options' => [
        'Text' => 'Text',
        'Audio' => 'Audio',
        'Video' => 'Video',
      ],
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#options' => [
        'Translation' => 'Translation',
        'Commentary' => 'Commentary',
        'Moolam' => 'Moolam',
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Source Node'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Stores the newly added source node to the table heritage_source_info,.
   *
   * And creates the corresponding fields in the content type.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $format_tmp_array = []; $params = [];
    $format = '';
    $textid = $form_state->getValue('textid');
    $params['title'] = $form_state->getValue('title');
    $params['language'] = $form_state->getValue('language');
    $params['author'] = $form_state->getValue('author');
    $format_array = $form_state->getValue('format');
    $params['type'] = $form_state->getValue('type');
    if ($format_array['Text'] != '0') {
      $format_tmp_array[] = 'Text';
    }
    if ($format_array['Audio'] != '0') {
      $format_tmp_array[] = 'Audio';
    }
    if ($format_array['Video'] != '0') {
      $format_tmp_array[] = 'Video';
    }
    $params['format'] = $format_tmp_array;
    $result = _add_source_info($params, $textid);
    $url = Url::fromRoute('heritage_schema.addtoc', ['node' => $textid]);
    return $form_state->setRedirectUrl($url);
  }

}
