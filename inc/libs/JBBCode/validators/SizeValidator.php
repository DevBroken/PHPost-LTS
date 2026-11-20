<?php

namespace JBBCode\validators;

use JBBCode\InputValidator;

/**
 * Un InputValidator para valores de tamaño de texto válidos
 *
 * @author Kmario19
 * @since Jul 2015
 * @update Miguel92 - 2026
 */
class SizeValidator implements InputValidator {

    /**
     * Validates numeric size values > 0
     *
     * @param string $input
     * @return bool
     */
    public function validate(string $input): bool {
        // is_numeric() ya cubre strings numéricos, floats, etc
        return is_numeric($input) && (float) $input > 0;
    }

}
