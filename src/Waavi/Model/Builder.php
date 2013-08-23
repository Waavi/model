<?php namespace Waavi\Model;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder {

	protected $relatedQuery;

	/**
	 * Filters the result set by querying related models. Allows for closures, but not deep nested relationships.
	 * Every call to this function triggers a database query, which is then translated into a whereIn.
	 *
	 * This means this will not work if there are more than 10 000 models that satisfy the restriction.
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
		if (is_callable($model)) {
			return $this->whereRelatedNested($model, $boolean);
		}

		$relation = $this->getRelation($model);
		$relatedTable = $relation->getRelated()->getTable();
		$ids = $this->initRelatedQuery($model, $relation)->where("$relatedTable.$column", $operator, $value)->lists('id');

		if (empty($ids)) {
			return $this->whereNull('id', $boolean);
		}
		return $this->whereIn('id', $ids, $boolean);
	}

	/**
	 * Same as whereRelated but with boolean OR applied.
	 *
	 * @param  string  $model
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function orWhereRelated($model, $column = null, $operator = null, $value = null)
	{
		return $this->whereRelated($model, $column, $operator, $value, 'or');
	}

	/**
	 * Same as whereRelated but when trying to find records that are NOT related.
	 *
	 * @param  string  $model
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function whereNotRelated($model, $column = null, $operator = null, $value = null, $boolean = 'and')
	{
		if (is_callable($model)) {
			return $this->whereRelatedNested($model, $boolean);
		}

		$relation = $this->getRelation($model);
		$relatedTable = $relation->getRelated()->getTable();
		$ids = $this->initRelatedQuery($model, $relation)->where("$relatedTable.$column", $operator, $value)->lists('id');

		if (empty($ids)) {
			return $this;
		}
		return $this->whereNotIn('id', $ids, $boolean);
	}

	/**
	 * Same as whereNotRelated but with boolean OR applied.
	 *
	 * @param  string  $model
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function orWhereNotRelated($model, $column = null, $operator = null, $value = null)
	{
		return $this->whereNotRelated($model, $column, $operator, $value, 'or');
	}

	/**
	 * Initializes the database query that will look for the ids of related tuples.
	 *
	 * @param  string  $model
	 * @param  Illuminate\Database\Eloquent\Relations\Relation  $relation
	 * @return Illuminate\Database\Query\Builder
	 */
	protected function initRelatedQuery($model, $relation)
	{
		$parentTable 	= $relation->getParent()->getTable();
		$relatedTable = $relation->getRelated()->getTable();
		$fk 					= $relation->getForeignKey();
		$relationType = str_replace('Illuminate\\Database\\Eloquent\\Relations\\', '', get_class($relation));

		$query = DB::table($parentTable)->select("$parentTable.id");

		switch($relationType) {
			default:
				return $this;
			case 'BelongsTo':
				$query->join($relatedTable, "$relatedTable.id", '=', "$parentTable.$fk");
				break;
			case 'HasOne': case 'HasMany': case 'MorphOne': case 'MorphMany':
				$query->join($relatedTable, "$parentTable.id", '=', "$fk");
				break;
			case 'BelongsToMany':
				$table = $relation->getTable();
				$otherKey = $relation->getOtherKey();
				$query->join($table, "$parentTable.id", '=', "$fk")->join($relatedTable, "$relatedTable.id", '=', "$otherKey");
				break;
		}

		return $query;
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