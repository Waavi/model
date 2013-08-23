<?php namespace Waavi\Model;

use Illuminate\Support\MessageBag;

/**
 * Used when validation fails. Contains the invalid model for easy analysis.
 * Class InvalidModelException
 * @package LaravelBook\Ardent
 */
class InvalidModelRelationException extends \RuntimeException {

	/**
	 * The invalid model relation:
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * The message bag instance containing validation error messages
	 *
	 * @var \Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * Receives the invalid model and sets the model properties.
	 *
	 * @param string $model
	 */
	public function __construct( $model )
	{
		$this->model = $model;
		$errors = new MessageBag;
		$errors->add('Invalid model relation key', "The relation key $model is not valid");
	}

	/**
	 * Returns the model with invalid attributes.
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Returns directly the message bag instance with the model's errors.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getErrors()
	{
		return $this->errors;
	}

}