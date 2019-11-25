<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\node\Entity\Node;

/**
 * Provides a resource to get status of a text.
 *
 * @RestResource(
 *   id = "get_text_toc",
 *   label = @Translation("Get TOC of Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textid}/toc",
 *   }
 * )
 */
class GetTextTOC extends ResourceBase {

   /**
   * Responds to GET requests for retrieving the status of a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($textid = NULL) {

    // Verify if the textid exists.
    $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
    $toc_info = [];

    $structure = [];

    if (isset($textid) && $textid > 0) {
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
      // Load the node.
      $text_node = Node::load($textid);
      $machine_name = $text_node->field_machine_name->value;
      $level_labels = explode(',', $text_node->field_level_labels->value);

      $topLevelTerms = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE bundle=:bundle AND parent_target_id = 0)", [':bundle' => $machine_name])->fetchAll();
      $topLevelTermsCount = count($topLevelTerms);

      $query = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE name LIKE 'Chapter%' AND vid = :textname ORDER BY tid ASC", [':textname' => $textname])->fetchAll();
      for ($i = 0; $i < count($query); $i++) {
        $sublevels = calculate_sublevels($textname, $query[$i]->tid);
        $structure[$query[$i]->name] = (int) $sublevels;
      }

      $toc_info['level_labels'] = $level_labels;
      $toc_info['total_first_level_terms'] = $topLevelTermsCount;
      $toc_info['structure'] = $structure;

      $message = $toc_info;
      $statuscode = 200;

    }
    else {
      $message = [
        'success' => 0,
        'message' => 'text does not exist',
      ];
      $statuscode = 404;
    }

    return new ModifiedResourceResponse($message, $statuscode);

  }

}

// Function calculate_sublevels($textname,$chapter) {
// $chapter_tid = db_query("SELECT tid FROM `taxonomy_term_field_data` WHERE name = :chapter AND vid = :textname",[':chapter' => $chapter,'textname' => $textname])->fetchField();
// $sublevels = db_query("SELECT field_sub_levels_value FROM `taxonomy_term__field_sub_levels` WHERE entity_id = :chapterid AND bundle = :textname", [':chapterid' => $chapter_tid,':textname' => $textname])->fetchField();
// return $sublevels;
// }.
