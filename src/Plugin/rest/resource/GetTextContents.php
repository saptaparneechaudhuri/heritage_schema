<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a resource to get status of a text.
 *
 * @RestResource(
 *   id = "get_text_contents",
 *   label = @Translation("Get the contents of Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textname}",
 *   }
 * )
 */
class GetTextContents extends ResourceBase {

  /**
   * Responds to GET requests for retrieving the contents of a text.
   *
   * @param textname
   *   Unique name of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($textname = NULL) {

    // Get the textid from the textname.
    $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname", [':textname' => $textname])->fetchField();

    // Set a default langcode if langcode is not given as a parameter in the GET request.
    $langcode = 'dv';

    // Langcode from the GET parameters.
    if (isset($_GET['language'])) {

      $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

      $requested_language = ucfirst($_GET['language']);

      foreach ($languages as $language) {
        if ($requested_language == $language->getName()) {
          $langcode = $language->getId();

        }
      }

    }

    $contents = [];

    // GET the field for that textid.
    if (isset($textid) && $textid > 0) {

      $position = $_GET['position'];

      $term_position = db_query('SELECT entity_id FROM `taxonomy_term__field_position` WHERE field_position_value = :position AND bundle = :bundle', [':position' => $position, ':bundle' => $textname])->fetchField();

      $entityid = db_query('SELECT entity_id FROM `node__field_positional_index` WHERE field_positional_index_target_id = :term_position AND langcode = :langcode', [':term_position' => $term_position, ':langcode' => $langcode])->fetchField();

      // Make a query for the english node.
      $entityid_en = db_query('SELECT entity_id FROM `node__field_positional_index` WHERE field_positional_index_target_id = :term_position AND langcode = :langcode', [':term_position' => $term_position, ':langcode' => 'en'])->fetchField();

      // Once the entity id for the node with english is found
      // Load the node.
      $node = Node::load($entityid_en);

      // Text node. Loaded to check if field exists
      $text_node = Node::load($entityid);

      // Select metadata using entityid as nid.
      $meta_data = db_query('SELECT metadata FROM `heritage_field_meta_data` WHERE nid = :entityid AND language = :langcode', [':entityid' => $entityid, ':langcode' => $langcode])->fetchField();

      $parameters = $_GET;

      foreach ($parameters as $key => $parameter) {
        $parameter_info = explode('_', $key);

        if ($parameter_info[0] == 'field') {
          if (count($parameter_info) >= 3) {

            $field = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3];

            $field_name = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3] . '_value';

            $table_name = 'node__field_' . $textname . '_' . $parameter_info[2] . '_' . $parameter_info[3];

            // Check if the field exists
            if (isset($text_node->{$field}->value)) {

              // Get the content for the field from table.
              $content_present = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid, ':langcode' => $langcode])->fetchField();

              $contents['title'] = $position;

              $contents[$field_name] = $content_present;
              // json_decode converts a string to a json object.
              $contents['meta_data'] = json_decode($meta_data, TRUE);
              $contents['field_gita_10605_text'] = $node->field_gita_10605_text->value;
              $contents['field_gita_10609_text'] = $node->field_gita_10609_text->value;
              $contents['field_gita_10606_text'] = $node->field_gita_10606_text->value;
              $contents['field_gita_10612_text'] = $node->field_gita_10612_text->value;
              $contents['field_gita_10610_text'] = $node->field_gita_10610_text->value;
              $contents['field_gita_10611_text'] = $node->field_gita_10611_text->value;
              $contents['field_gita_10608_text'] = $node->field_gita_10608_text->value;
              $contents['field_gita_10607_text'] = $node->field_gita_10607_text->value;

              $message = $contents;
              $statuscode = 200;

            }
            else {
              $message = [
                'success' => 0,
                'message' => "Field does not exist",

              ];
              $statuscode = 404;
            }

          }
        }
      }

    }

    else {

      $message = [
        'success' => 0,
        'message' => 'Text does not exist',
      ];

      $statuscode = 404;

    }

    return new ModifiedResourceResponse($message, $statuscode);

  }

}
