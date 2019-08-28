<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to edit sources of a text.
 *
 * @RestResource(
 *   id = "edit_source_info",
 *   label = @Translation("Edit Sources of a Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textname}/edit/source_node/{sourceid}",
	   "https://www.drupal.org/link-relations/create" = "/api/{textname}/edit/source_node/{sourceid}"
 *   }
 * )
 */
class EditSourceNode extends ResourceBase {

  /**
   * Responds to POST requests for editing a source of a text.
   *
   * @param textname
   *   Unique Machine Name of the text
   *
   * @param sourceid
   *   Unique ID of the source
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
	public function post($textname = NULL, $sourceid = NULL, $arg) {
		$in_correct = 0;
		$connection = \Drupal::database();
		$info_present = $connection->query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname AND bundle = 'heritage_text'", array(':textname' => $textname))->fetchField();
		if(isset($info_present) && $info_present > 0){
			$textid = $info_present;
			$source_present = db_query("SELECT COUNT(*) FROM `heritage_source_info` WHERE text_id = :textid AND id = :sourceid", array(':textid' => $textid, ':sourceid' => $sourceid))->fetchField();
			if($source_present == 1){
				if(count($arg) == 0){
					$message = [
						'success' => 0,
						'message' => 'required parameters missing'
					];
					$statuscode = 400;
				}
				else{
					if(!isset($arg['title']) || !isset($arg['language']) || !isset($arg['author']) || !isset($arg['format']) || !isset($arg['type']) ){
						$message = [
							'success' => 0,
							'message' => 'required parameters missing'
						];
						$statuscode = 400;
					}
					else{
						if($arg['type'] != 'translation' && $arg['type'] != 'commentary' && $arg['type'] != 'moolam'){
							$message = [
								'success' => 0,
								'message' => 'required parameters missing'
							];
							$statuscode = 400;
						}
						else {
							for($j=0; $j<count($arg['format']); $j++){
								if($arg['format'][$j] != 'text' && $arg['format'][$j] != 'audio' && $arg['format'][$j] != 'video'){
									$in_correct = 1;
								}
							}
							if($in_correct == 1){
								$message = [
									'success' => 0,
									'message' => 'required parameters missing'
								];
								$statuscode = 400;
							}
							else{
								$arg['format'] =  array_unique($arg['format']);
								$result = _update_source_info($arg, $sourceid);
								$message = [
									'success' => 1,
									'message' => 'source updated'
								];
								$statuscode = 200;
							}
						}
					}
				}
			}
			else{
				$message = [
					'success' => 0,
					'message' => 'page not found'
				];
				$statuscode = 404;
			}
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
