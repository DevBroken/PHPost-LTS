<?php

namespace JBBCode\validators;

use JBBCode\InputValidator;

/**
 * An InputValidator for urls. This can be used to make [url] bbcodes secure.
 *
 * @author jbowens
 * @since May 2013
 */
class UrlValidator implements InputValidator
{

    /**
     * Returns true iff $input is a valid url.
     *
     * @param string $input  the string to validate
     * @return boolean
     */
    public function validate($input): bool
    {
        $valid = filter_var($input, FILTER_VALIDATE_URL);
        return !!$valid;
    }
}
