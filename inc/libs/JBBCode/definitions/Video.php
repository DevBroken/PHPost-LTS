<?php

require_once dirname(__DIR__, 1)  . '/CodeDefinition.php';

class Video extends JBBCode\CodeDefinition {

   	public function __construct() {
	  	parent::__construct();
	  	$this->setTagName("video");
   	}

	public function asHtml(\JBBCode\ElementNode $el): string {
		$content = "";
		foreach ($el->getChildren() as $child)
			$content .= $child->getAsBBCode();

		$foundMatch = preg_match('/v=([A-z0-9=\-]+?)(&.*)?$/i', $content, $matches);
		if ($foundMatch) {
			return "<iframe class=\"mx-auto\" width=\"640\" height=\"390\" src=\"https://www.youtube.com/embed/" . $matches[1] . "\" frameborder=\"0\" allowfullscreen></iframe>";
		}
		return $el->getAsBBCode();
	}

}
