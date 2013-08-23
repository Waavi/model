# Better Eloquent models for Laravel

WaaviModel is inspired by Ardent and Aware model validators for Eloquent. The following features are provided:

	- Allows Laravel to query related models.
	- AutoValidate on model save.
	- Allows for unique constraint in validation rules to auto ignore the current model when updating
	- Flashes input data when a save fails.

## Setup

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

  /**
   * Validation custom messages
   *
   * @var array
   */
  public $rules = array(
  	'required' => 'The :attribute field is required.',
  );
}
```

All of Laravel's validation rules are available, as well as any custom validation rules you've setup. When saving a model, the validation is run automatically. Validation errors may be retrieved through $model->errors(). Usage example:

```php
	$input = Input::all();
	$success = Post::fill($input)->save();
	if ($success) {
		return Redirect::route('...');
	} else {
		return Redirect::route('...')->withErrors($article->errors());
	}
```

In case you want to skip validation, you may do so using ```$model->forceSave();```. You may check that a model is valid before saving using ```$model->isValid();```

Rules may be accessed and modified at runtime using:

```php
$rules = array('title'	=> 'required|between:5,160');

$model->getRules();                                 // Returns the model's validation rules.
$model->setRules($rules);                           // Switches the model's validation rules
$model->setRule('slug', 'required|unique:posts');   // Adds or replaces a validation rule
$$model->removeRule('text');                        // Removes rules for a field
```

Custom messages are accessed in the same way:

```php
$messages = array('required'	=> 'The :attribute field is required.');

$model->getCustomMessages();                                    // Returns the model's custom messages.
$model->setCustomMessages($messages);                           // Switches the model's custom messages.
$model->setCustomMessage('required', ':attribute is required'); // Adds or replaces a custom messages.
$$model->removeCustomMessage('required');                       // Removes a custom message.
```


### Querying related models

WaaviModels extend Eloquent's ability to query related models. This is useful when queries must be built dynamically and require you to access values in related models.

Let us explain it through an example. Say you have the following models:

```php
class User extends WaaviModel {
	public function posts() {
		return $this->hasMany('Post');
	}
}

class Post extends WaaviModel {
	public function author() {
		return $this->belongsTo('User');
	}
	public function comments() {
		return $this->hasMany('Comment');
	}
	public function tags() {
		return $this->hasMany('Comment');
	}
}

class Tag extends WaaviModel {
	public function posts() {
		return $this->hasMany('Post');
	}
}

class Comment extends WaaviModel {
	public function post() {
		return $this->belongsTo('User');
	}
}
```

Say you want all Posts made by users named John or Jane that have been tagged as 'News'. This would be hard to do with Eloquent, but with WaaviModel you have an alternative. You may call whereRelated($relationshipName, $column, $operator, $value) to filter the results:

```php
$posts = Post::whereRelated('tags', 'value', '=', 'News')
  ->whereRelated(function($query)
  {
    $query->whereRelated('author', 'name', '=', 'John')
      ->orWhereRelated('author', 'name', '=', 'Jane');
	})
  ->get();
```

If you want to retrive all posts other than those posted by people named John its just as easy.

```php
$posts = Post::whereNotRelated('author', 'name', '=', 'John')->get()
```

How about getting all of the post comments made in articles posted by John?

```php
$comments = Comment::whereRelated('post.author', 'name', '=', 'John')->get()
```

All relationships supported by Eloquent are supported by WaaviModel, including MorphOne and MorphMany. The list of available filters is:

```php
WaaviModel::whereRelated($relationshipName, $column, $operator, $value);

WaaviModel::orWhereRelated($relationshipName, $column, $operator, $value);

WaaviModel::whereNotRelated($relationshipName, $column, $operator, $value);

WaaviModel::orWhereNotRelated($relationshipName, $column, $operator, $value);
```

Closures as well as deep relationships as supported, as you saw in the previous examples. However, subqueries are not yet supported, and $column, $operator and $value must be specified except when using a Closure.

Calls to whereRelated methods are internally compiled to whereIn, whereNotIn or whereNull (when no records satisfy the constraint). To do so, every call queries the database to see which records comply with the specified constraint. This has several drawbacks:

	- Most common databases allow for a limited number of elements (around 10.000) in whereIn clauses. Laravel has the same limitation when using eager loading, which is done in the same way.
	- Each whereRelated call queries the database separately, so heavy use will impact performance.
