## Better Eloquent models for Laravel

WaaviModel is inspired by Ardent and Aware model validators for Eloquent. The following features are provided:

	- AutoValidate on model save.
	- Allows for unique constraint in validation rules to auto ignore the current model when updating
	- Flashes input data when a save fails.

## Installation

Edit composer.json:

	"require": {
		"waavi/model": "dev-master"
	},

Models must extend the Waavi\Model\WaaviModel class:

	<?php

		use Waavi\Model\WaaviModel;

		class MyModel extends WaaviModel {

			...

		}


## Usage

### Basic example

To be continued...
