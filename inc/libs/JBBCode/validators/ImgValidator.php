<?php

namespace JBBCode\validators;

require_once dirname(__DIR__, 1) . '/InputValidator.php';

/**
 * Un InputValidator para url de imagen válida
 *
 * @author PHPost
 * @since Nov 2015
 */
class ImgValidator implements \JBBCode\InputValidator {

	/**
	 * Retorna true si $input es un enlace que muestra una imagen con los formatos más utilizados
	 *
	 * @param $input texto para validar
	 */
	public function validate($input): bool {
		$img['url'] = $input;
		$img['size'] = getimagesize($img['url']);
		$img['type'] = $img['size'][2];
		$allow = [IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP];

		return (bool)(in_array($img['type'], $allow, true));
	}

}
