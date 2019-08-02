<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to add sources to a text.
 *
 * @RestResource(
 *   id = "get_sources_list",
 *   label = @Translation("Get the list of Sources"),
 *   uri_paths = {
 *     "canonical" = "/api/{textname}/sources",
 *   }
 * )
 */
class GetSourcesList extends ResourceBase {

  /**
   * Responds to GET requests for retrieving the list of sources of a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
	public function get($textname = NULL) {
		$connection = \Drupal::database();
		$sources = array();
		$textid = $connection->query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname", array(':textname' => $textname))->fetchField();
		if(isset($textid) && $textid > 0){
			$available_sources = db_query("SELECT * FROM `heritage_source_info` WHERE text_id = :textid ORDER BY language DESC", array(':textid' => $textid))->fetchAll();
			if(count($available_sources) > 0){
				for($i = 0; $i<count($available_sources); $i++){
					$source_info = array();
					$source_info['id'] = $available_sources[$i]->id;
					$source_info['title'] = $available_sources[$i]->title;
					$source_info['author'] = $available_sources[$i]->author;
					$source_info['language'] = $available_sources[$i]->language;
					$source_info['format'] = $available_sources[$i]->format;
					$source_info['type'] = $available_sources[$i]->type;
					$source_info['field_name'] = 'field_'.$textname.'_'.$source_info['id'];
					$sources[] = $source_info;
				}
			}
			$message = $sources;
			$statuscode = 200;
		}
		else{
			$message = [
				'success' => 0,
				'message' => 'page not found'
			];
			$statuscode = 404;
		}
		return new ModifiedResourceResponse($message, $statuscode);
	}	
}
