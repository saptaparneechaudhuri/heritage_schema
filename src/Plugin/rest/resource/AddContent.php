<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a resource to add sources to a text.
 *
 * @RestResource(
 *   id = "add_text_content",
 *   label = @Translation("Add Contents to a Text"),
 *   uri_paths = {
 *     "canonical" = "/api/add/{textid}",
 * "https://www.drupal.org/link-relations/create" = "/api/add/{textid}"
 *   }
 * )
 */
class AddContent extends ResourceBase {

  /**
   * Responds to POST requests for adding contents to a text.
   *
   * @param textname
   *   Unique Machine Name of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object   *
   */
  public function post($textid = NULL, $arg) {

    $info_present = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
    $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $info_present])->fetchField();
    // print_r($textname);exit;
    $text_node = Node::Load($info_present);

    // Check if the source of that id is present.
    if (count($arg) == 0) {
      $message = [
        'success' => 0,
        'message' => 'required parameters missing(count($arg) == 0)',
      ];
      $statuscode = 400;

    }

    else {
      for ($i = 0; $i < count($arg); $i++) {

        // Loop through the args to extract the sourceid from field name in the post body.
        foreach ($arg[$i] as $key => $value) {
          $parameter_info = explode('_', $key);

          if ($parameter_info[0] == 'field') {
            $sourceid = $parameter_info[2];

            $source_present = db_query("SELECT COUNT(*) FROM `heritage_source_info` WHERE id = :sourceid", [':sourceid' => $sourceid])->fetchField();
            if ($source_present == 1) {

              $field_name = 'field_' . $textname . '_' . $sourceid . '_' . $parameter_info[3];

              if (!isset($arg[$i][$field_name])) {
                $message = [
                  'success' => 0,
                  'message' => 'required parameters missing(incorrect field name)',
                ];
                $statuscode = 400;
                break;

              }
              else {
                if (!isset($arg[$i]['positional_index']) || !isset($arg[$i]['language'])) {

                  $message = [
                    'success' => 0,
                    'message' => 'required parameters missing(incorrect arguments)',
                  ];
                  $statuscode = 400;
                  break;

                }

                else {

                  // Convert the language into language code.
                  $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

                  foreach ($languages as $language) {
                    if ($arg[$i]['language'] == $language->getName()) {
                      $arg[$i]['language'] = $language->getId();
                    }

                  }

                  // $arg[$i]['language'] = "dv";
                  // Make the db querries here.
                  //  $textid = db_query("SELECT entity_id FROM `node__field_positional_index` WHERE langcode = :language AND field_positional_index_target_id IN (SELECT entity_id FROM `taxonomy_term__field_position` WHERE field_position_value = :positional_index)", ['language' => $arg[$i]['language'], ':positional_index' => $arg[$i]['positional_index']])->fetchField();
                  $textid = db_query("SELECT nid FROM `node_field_data` WHERE title = :title AND type = :textname AND langcode = :language", [':title' => $arg[$i]['positional_index'], ':textname' => $textname, ':language' => $arg[$i]['language']])->fetchField();

                  // print_r($textid);exit;
                  if (isset($textid) && $textid > 0) {
                    // Insert the content.
                    $node = Node::load($textid);
                    $node->{$field_name}->value = $arg[$i][$field_name];
                    $node->{$field_name}->format = 'full_html';

                    $node->save();

                    $message = [
                      'success' => 1,
                      'message' => 'content added',
                    ];
                    $statuscode = 200;

                  }
                  else {

                    // Create a text node, if the text does not exist.
                    $str = explode('.', $arg[$i]['positional_index']);
                    $numLevels = count($str);
                    $taxonomy_ids = [];

                    for ($i = 0; $i < $numLevels; $i++) {
                      if ($i == 0) {
                        $position = $str[0];
                      }
                      else {
                        $position = $position . '.' . $str[$i];
                      }
                      $taxonomy_ids[$i]['target_id'] = db_query("SELECT entity_id FROM `taxonomy_term__field_position` WHERE bundle = :bundle AND field_position_value = :position", [':bundle' => $textname, ':position' => $position])->fetchField();

                    }

                    $language_id = db_query('SELECT tid FROM `taxonomy_term_field_data` WHERE name = :language', [':language' => $arg[$i]['language']])->fetchField();
                    $arg[$i]['language'] = $language_id;
                    // print_r($arg[$i]['language']);exit;.
                    $node = entity_create('node', [

                      'type' => $textname,
                      'title' => $arg[$i]['positional_index'],
                      'langcode' => $arg[$i]['language'],

                      'field_positional_index' => $taxonomy_ids,
                      $field_name => ['value' => $arg[$i][$field_name], 'format' => 'full_html'],

                    ]
                      );
                    $node->save();

                    $message = [
                      'success' => 1,
                      'message' => 'text created and content posted',
                    ];
                    $statuscode = 200;
                    /* if (count($str) == 2) {
                    // Check the level labels.
                    $level_labels = explode(',', $text_node->field_level_labels->value);

                    // $chapter_name = 'Chapter ' . $str[0];
                    //  $sloka_name = 'Sloka ' . $str[1];
                    $chapter_name = $level_labels[0] . ' ' . $str[0];
                    $sloka_name = $level_labels[1] . ' ' . $str[1];

                    // $language_id = db_query('SELECT tid FROM `taxonomy_term_field_data` WHERE name = :language', [':language' => $arg[$i]['language']])->fetchField();
                    // $arg[$i]['language'] = $language_id;
                    $chapter_id = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE vid = :vid AND name = :name", [':vid' => $textname, ':name' => $chapter_name])->fetchField();

                    $sloka_id = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE vid = :vid AND name = :name AND tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE parent_target_id = :parent_tid)", [':vid' => $textname, ':name' => $sloka_name, ':parent_tid' => $chapter_id])->fetchField();

                    $node = entity_create('node', [

                    'type' => $textname,
                    'title' => $arg[$i]['positional_index'],
                    'langcode' => $arg[$i]['language'],

                    'field_positional_index' => [['target_id' => (int) $chapter_id], ['target_id' => (int) $sloka_id]],
                    $field_name => ['value' => $arg[$i][$field_name], 'format' => 'full_html'],

                    ]
                    );
                    $node->save();

                    $message = [
                    'success' => 1,
                    'message' => 'text created and content posted',
                    ];
                    $statuscode = 200;
                    }
                    if (count($str) == 3)
                    $level_labels = explode(',', $text_node->field_level_labels->value);

                    $kanda_name = $level_labels[0] . ' ' . $str[0];
                    $sarga_name = $level_labels[1] . ' ' . $str[1];
                    $sloka_name = $level_labels[2] . ' ' . $str[2];

                    $kanda_tid = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE vid = :vid AND name = :name", [':vid' => $textname, ':name' => $kanda_name])->fetchField();

                    // Sarga tid.
                    $sarga_tid = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE vid = :vid AND name = :name AND tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE parent_target_id = :parent_tid)", [':vid' => $textname, ':name' => $sarga_name, ':parent_tid' => $kanda_tid])->fetchField();

                    // Sloka tid.
                    $sloka_tid = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE vid = :vid AND name = :name AND tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE parent_target_id = :parent_tid)", [':vid' => $textname, ':name' => $sloka_name, ':parent_tid' => $sarga_tid])->fetchField();

                    $node = entity_create('node', [

                    'type' => $textname,
                    'title' => $arg[$i]['positional_index'],
                    'langcode' => $arg[$i]['language'],

                    'field_positional_index' => [['target_id' => (int) $kanda_tid], ['target_id' => (int) $sarga_tid], ['target_id' => (int) $sloka_tid]],
                    $field_name => ['value' => $arg[$i][$field_name], 'format' => 'full_html'],

                    ]
                    );

                    $node->save();
                    $message = [
                    'success' => 1,
                    'message' => 'text created and content posted',
                    ];
                    $statuscode = 200;

                    } */

                  }

                }
              }

            }
            else {
              $message = [
                'success' => 0,
                'message' => 'source of the given id does not exist',
              ];

              $statuscode = 404;
            }
          }
        }

      }

    }

    return new ModifiedResourceResponse($message, $statuscode);

  }

}
