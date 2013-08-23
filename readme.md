# Better Eloquent models for Laravel

WaaviModel is inspired by Ardent and Aware model validators for Eloquent. The following features are provided:

	- AutoValidate on model save.
	- Allows for unique constraint in validation rules to auto ignore the current model when updating
	- Flashes input data when a save fails.
	- Allows to filter by related models.

## Setup

Edit composer.json:

	"require": {
		"waavi/model": "dev-master"
	},

Update using composer install

Models must extend the Waavi\Model\WaaviModel class:

	<?php

		use Waavi\Model\WaaviModel;

		class MyModel extends WaaviModel {

			...

		}


## Validation

WaaviModel uses Laravel's [Validator class](http://laravel.com/docs/validation), therefore validation rules, custom messages and custom validation methods are all available.

```php
use Waavi\Model\WaaviModel;

class Post extends WaaviModel {

	protected $table = 'posts';

	/**
   * Validation rules
   *
   * @var array
   */
  public $rules = array(
  	'title'	=> 'required|between:5,160',
  	'slug'	=> 'required|unique:posts',
  	'text'	=> 'required',
  );
}
```

When saving a model, the validation is run automatically.

Validation errors may be retrieved through $model->errors().

Saving may be force through $model->forceSave();

Rules may be modified through $model->setRules($rules), $model->setRule($field, $rules) and $model->removeRule($field). The same schema applies to custom messages.

### Querying related models

WaaviModels extend Eloquent's ability to query related models. If for example you want to retrieve all Posts by users called John, you may:

```php
$posts = Post::whereRelated('user', 'name', '=', 'John')->get()
```

If you do not want posts by people name John, it's just as easy:

```php
$posts = Post::whereNotRelated('user', 'name', '=', 'John')->get()
```

You may of course use the suffix or:

```php
$posts = Post::whereRelated('user', 'name', '=', 'John')->orWhereRelated('user', 'name', '=', 'Jane')->get()
```

Closures are also supported:

```php
$posts = Post::with('user')
	->whereRelated(function($query) use ($author)
	{
	  $query->whereRelated('user', 'name', '=', 'John')
	    ->orWhereRelated('user', 'name', '=', 'Jane');
	})
	->where('title', 'like', '%'.Input::get('title').'%');
```

We are currently working on implementing the following:

Nested relationships

```php
$posts = Post::whereRelated('user.country', 'name', '=', 'Spain')->get()
```

Performance improvements.

## Caution

Right now, every call to whereRelated and sibling methods triggers a database query. This is of course suboptimal, and we're working on improving the performance so that at most two queries are done.

As happens with Eloquent's with function, whereRelated also relies on whereIn and whereNotIn underneath, therefore your queries will be limited to around 10.000 records in the most common configurations for popular databases. Please take care when using WaaviModel on large datasets.