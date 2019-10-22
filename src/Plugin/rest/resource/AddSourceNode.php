<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Provides a resource to add sources to a text.
 *
 * @RestResource(
 *   id = "add_source_info",
 *   label = @Translation("Add Sources to a Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textid}/add/source_node",
 * "https://www.drupal.org/link-relations/create" = "/api/{textid}/add/source_node"
 *   }
 * )
 */
class AddSourceNode extends ResourceBase {

  /**
   * Responds to POST requests for adding sources to a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post($textid = NULL, $arg) {
    $in_correct = 0;
    $connection = \Drupal::database();
    // $info_present = $connection->query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname AND bundle = 'heritage_text'", [':textname' => $textname])->fetchField();
    $info_present = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

    if (isset($info_present) && $info_present > 0) {
      $textid = $info_present;
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

      if (count($arg) == 0) {
        $message = [
          'success' => 0,
          'message' => 'required parameters missing(count($arg) == 0)',
        ];
        $statuscode = 400;
      }
      else {
        for ($i = 0; $i < count($arg); $i++) {
          if (!isset($arg[$i]['title']) || !isset($arg[$i]['language']) || !isset($arg[$i]['author']) || !isset($arg[$i]['format']) || !isset($arg[$i]['type'])) {
            $message = [
              'success' => 0,
              'message' => 'required parameters missing(incorrect arguments)',
            ];
            $statuscode = 400;
            break;
          }
          else {
            if ($arg[$i]['type'] != 'translation' && $arg[$i]['type'] != 'commentary' && $arg[$i]['type'] != 'moolam') {
              $message = [
                'success' => 0,
                'message' => 'required parameters missing(incorrect type)',
              ];
              $statuscode = 400;
              break;
            }
            else {
              for ($j = 0; $j < count($arg[$i]['format']); $j++) {
                if ($arg[$i]['format'][$j] != 'text' && $arg[$i]['format'][$j] != 'audio' && $arg[$i]['format'][$j] != 'video') {
                  $in_correct = 1;
                }

              }

              if ($in_correct == 1) {
                $message = [
                  'success' => 0,
                  'message' => 'required parameters missing(incorrect format)',
                ];
                $statuscode = 400;
                break;
              }
              else {
                $arg[$i]['format'] = array_unique($arg[$i]['format']);
                // $node = entity_create('node',
                // [
                //   'type' => 'source_node',
                //   'title' => $arg[$i]['title'],
                // 'field_format' => $arg[$i]['format'],
                //   'field_type' => $arg[$i]['type'],
                //   'field_language' => [
                //     'target_id' => (int) $arg[$i]['language'],
                //   ],
                //   'field_author' => [
                //     'target_id' => (int) $arg[$i]['author'],
                //   ],
                // ]
                // );
                // $node->save();
                // You have to pass the source node id in the add_source_info function
                // because source node id is needed.
                // $arg[$i]['source_nodeId'] = $node->id();
                // $languages = \Drupal::service('language_manager')->getLanguages();
                // foreach ($languages as $language) {
                //                                 if ($arg[$i]['language'] == $language->getName()) {
                //                                     $arg[$i]['language'] =  $language->getId();
                // }
                //                         }
                // Language field
                $language_id = db_query('SELECT tid FROM `taxonomy_term_field_data` WHERE name = :language', [':language' => $arg[$i]['language']])->fetchField();

                $arg[$i]['language'] = $language_id;

                // Author field.
                $vid = 'authors';
                $term_name = $arg[$i]['author'];

                $arg[$i]['author'] = check_taxonomy($vid, $term_name);

                $arg[$i]['source_nodeId'] = create_sourceNode($arg[$i]['title'], $arg[$i]['format'], $arg[$i]['type'], $arg[$i]['language'], $arg[$i]['author'], $textid);

                $result = _add_source_info($arg[$i], $textid);
                $message = [
                  'success' => 1,
                  'message' => 'source added',
                ];
                $statuscode = 200;

              }
            }
          }
        }
      }
    }
    else {
      $message = [
        'success' => 0,
        'message' => 'page not found',
      ];
      $statuscode = 404;
    }
    return new ModifiedResourceResponse($message, $statuscode);
  }

}
