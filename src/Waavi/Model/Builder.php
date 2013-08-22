<?php namespace Waavi\Model;

use Illuminate\Support\Facades\DB;

class Builder extends \Illuminate\Database\Eloquent\Builder {

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
	public function whereRelated($model, $column, $operator = null, $value = null, $boolean = 'and')
	{
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
					return $this->whereNull('id');
				}
				return $this->whereIn('id', $ids);
		}
	}

}