<?php

namespace Drupal\heritage_schema\Controller;

use Drupal\Core\Access\AccessResult;

/**
* Simple page controller.
*/
class HeritageText {
	/**
	* {@inheritdoc} Control Access to the TOC tab in nodes
	*/
	public function showtoc($node = NULL) {
		$node_info = \Drupal\node\Entity\Node::load($node);
		if($node_info->getType() == 'heritage_text') return AccessResult::allowed();
		else return AccessResult::forbidden();
	}
}
