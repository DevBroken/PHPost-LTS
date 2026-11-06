<?php

namespace JBBCode\validators;

require_once dirname(__DIR__, 1) . '/InputValidator.php';

/**
 * An InputValidator that allows for shortcut implementation
 * of a validator using callable types (a function or a \Closure).
 *
 * @author Kubo2
 * @since Feb 2020
 */
class FnValidator implements \JBBCode\InputValidator
{
	/**
	 * @var callable
	 */
	private $validator;

	/**
	 * Construct a custom validator from a callable.
	 * @param callable $validator
	 */
	public function __construct(callable $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Returns true iff the given input is valid, false otherwise.
	 * @param string $input
	 * @return boolean
	 */
	public function validate($input): bool
	{
		$validator = $this->validator; // FIXME: for PHP>=7.0 replace with ($this->validator)($input)
		return (bool) $validator($input);
	}
}
