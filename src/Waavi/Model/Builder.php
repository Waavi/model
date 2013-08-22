<?php namespace Waavi\Model;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder {

	/**
	 * Filters the result set by querying related models. This is a very limited method, as it only works when the
	 * parent model belongs to the related model and issues a database query everytime to retrieve a list of ids
	 * that it then translates to a whereIn call. It also doesn't allow for closures.
	 *
	 * This means this will not work if there are more than 10 000 models that satisfy the restriction, or if the
	 * model doesn't belong to the related model.
	 *
	 * @param  string  $model
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function whereRelated($model, $column = null, $operator = null, $value = null, $boolean = 'and')
	{
		if (is_callable($model))
		{
			return $this->whereRelatedNested($model, $boolean);
		}

		$relation = $this->getRelation($model);
		$relationType = str_replace('Illuminate\\Database\\Eloquent\\Relations\\', '', get_class($relation));
		switch($relationType) {
			default:
				return $this;
			case 'BelongsTo':
				$parentTable = $relation->getParent()->getTable();
				$relatedTable = $relation->getRelated()->getTable();
				$fk = $relation->getForeignKey();
				$ids = DB::table($parentTable)
					->select("$parentTable.id")
					->join($relatedTable, "$relatedTable.id", '=', "$parentTable.$fk")
					->where("$relatedTable.$column", $operator, $value)
					->lists('id');
				if (empty($ids)) {
					return $this->whereNull('id', $boolean);
				}
				return $this->whereIn('id', $ids, $boolean);
		}
	}

	public function orWhereRelated($model, $column = null, $operator = null, $value = null)
	{
		return $this->whereRelated($model, $column, $operator, $value, 'or');
	}

	/**
	 * Add a nested where statement to the query.
	 *
	 * @param  \Closure $callback
	 * @param  string   $boolean
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function whereRelatedNested(Closure $callback, $boolean = 'and')
	{
		// To handle nested queries we'll actually create a brand new query instance
		// and pass it off to the Closure that we have. The Closure can simply do
		// do whatever it wants to a query then we will store it for compiling.
		$type = 'Nested';

		$builder = new Builder($this->query->newQuery());
		$builder->setModel($this->model);

		call_user_func($callback, $builder);

		// Once we have let the Closure do its things, we can gather the bindings on
		// the nested query builder and merge them into these bindings since they
		// need to get extracted out of the children and assigned to the array.
		$query = $builder->getQuery();
		if (count($query->wheres))
		{
			$this->getQuery()->wheres[] = compact('type', 'query', 'boolean');

			$this->getQuery()->mergeBindings($query);
		}

		return $this;
	}
}