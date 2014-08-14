<?php

class Foundry extends Eloquent
{

	/**
	 * The model table's indexes.
	 *
	 * @var array
	 */
	protected $indexes = array();

	/**
	 * The model table's columns.
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * The model's validation rules.
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Get all indexes for this table.
	 *
	 * @return array
	 */
	public function getIndexes()
	{
		if( count($this->indexes) > 0 ) return $this->indexes;

		$raw_indexes = DB::connection()
			->getDoctrineSchemaManager()
			->listTableIndexes($this->getTable());

		foreach( $raw_indexes as $raw )
		{
			$this->indexes[$raw->getName()] = [
				'columns' => $raw->getColumns(),
				'primary' => $raw->isPrimary(),
				'unique'  => $raw->isUnique(),
			];
		}

		return $this->indexes;
	}

	/**
	 * Get all columns for this table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		if( count($this->columns) > 0 ) return $this->columns;

		$raw_columns = DB::connection()
			->getDoctrineSchemaManager()
			->listTableColumns($this->getTable());

		foreach( $raw_columns as $raw )
		{
			// Check if there is an index on this column
			$matched_index = NULL;
			foreach( $this->getIndexes() as $index )
			{
				if( in_array($raw->getName(), $index['columns']) )
				{
					$matched_index = $index;
					break;
				}
			}

			$this->columns[$raw->getName()] = [
				'label'   => ( $raw->getComment() ? $raw->getComment() : NULL ),

				'is_email' => ( $raw->getType()->getName() == 'string' && str_contains($raw->getName(), 'email') ),
				'relationship' => str_replace('_id', '', $raw->getName()),

				'type'     => $raw->getType()->getName(),
				'default'  => $raw->getDefault(),
				'length'   => $raw->getLength(),
				'required' => $raw->getNotnull() ? true : false,

				'primary' => $matched_index['primary'],
				'unique'  => $matched_index['unique'],
			];
		}

		return $this->columns;
	}

	/**
	 * Get only editable columns.
	 *
	 * @return array
	 */
	public function getEditableColumns()
	{
		if ($this->totallyGuarded())
		{
			throw new MassAssignmentException($key);
		}

		$columns = array_diff_key($this->getColumns(),
			array_flip($this->hidden), array_flip($this->guarded), array_flip($this->getDates()));

		return $columns;
	}

	/**
	 * Get only visible columns.
	 *
	 * @return array
	 */
	public function getVisibleColumns()
	{
		$columns = array_diff_key($this->getColumns(), array_flip($this->hidden));

		return $columns;
	}

	/**
	 * Get validation rules.
	 *
	 * @return array
	 */
	public function getRules()
	{
		// Generate default validator rules
		foreach( $this->getColumns() as $name => $column )
		{
			if( ! $column['primary'] )
			{
				$rules = array();

				// If it's not nullable and not a boolean
				// Booleans are checkboxes so they should never be "required"
				if( $column['required'] && $column['type'] != 'boolean' )
				{
					$rules[] = 'required';
				}
				// If the column contains "email"
				if( $column['is_email'] )
				{
					$rules[] = 'email';
				}
				if( $column['unique'] )
				{
					$rules[] = 'unique:'.$this->getTable().','.$name.( $this->id ? ','.$this->id : '' );
				}

				if( count($rules) > 0 )
				{
					$this->rules[$name] = implode('|', $rules);
				}
			}
		}

		return $this->rules;
	}

}
