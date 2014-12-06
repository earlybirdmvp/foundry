<?php namespace Earlybird;

trait Foundry
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

		$raw_indexes = \DB::connection()
			->getDoctrineSchemaManager()
			->listTableIndexes($this->getTable());

		foreach( $raw_indexes as $raw )
		{
			$idx = new \stdClass();
			$idx->columns = $raw->getColumns();
			$idx->primary = $raw->isPrimary();
			$idx->unique  = $raw->isUnique();

			$this->indexes[$raw->getName()] = $idx;
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

		$raw_columns = \DB::select("SHOW COLUMNS FROM ".$this->getTable());

		foreach( $raw_columns as $raw )
		{
			// Check if there is an index on this column
			$matched_index = NULL;
			foreach( $this->getIndexes() as $index )
			{
				if( in_array($raw->Field, $index->columns) )
				{
					$matched_index = $index;
					break;
				}
			}

			$type = $raw->Type;
			$options = array();

			// Standard fields with length argument, e.g.
			// int(10), tinyint(1), char(60), varchar(100)
			if( preg_match('/([a-z]+)\(([0-9]+)\)/', $raw->Type, $matches) ) {
				$type = $matches[1];
				$length = $matches[2];

				if( in_array($raw->Field, $this->getFileColumns() ) ) {
					$type = 'file';
				}
				else if( $type == 'char' && str_contains($raw->Field, 'password') ) {
					$type = 'password';
				}
			}
			// Fields with two arguments, e.g.
			// decimal(8,2)
			else if( preg_match('/([a-z]+)\(([0-9]+,[0-9]+)\)/', $raw->Type, $matches) ) {
				$type = $matches[1];
				$length = $matches[2];
			}
			// Enum fields
			else if( preg_match('/([a-z]+)\(\'(.*)\'\)/', $raw->Type, $matches) ) {
				$raw_options = str_replace("','", ",", $matches[2]);
				$raw_options = explode(',', $raw_options);
				foreach( $raw_options as $opt ) { $options[$opt] = $opt; }
				$type = $matches[1];
			}

			if( $type == 'tinyint' && $length == 1 ) {
				$type = 'boolean';
			}

			$col = new \stdClass();
			$col->label = str_replace(['_id', '_'], ['', ' '], $raw->Field);
			//$col->label    = ( $raw->getComment() ? $raw->getComment() : NULL );
			$col->is_email = ( str_contains($type, 'varchar') && str_contains($raw->Field, 'email') );
			$col->relationship = camel_case(str_replace('_id', '', $raw->Field));

			$col->type     = $type;
			$col->default  = $raw->Default;
			$col->length   = $length;
			$col->options  = $options;
			$col->required = $raw->Null == 'NO' ? true : false;

			$col->primary = $matched_index->primary;
			$col->unique  = $matched_index->unique;

			$this->columns[$raw->Field] = $col;
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
			throw new \Illuminate\Database\Eloquent\MassAssignmentException($key);
		}

		$columns = array_diff_key($this->getColumns(),
			array_flip($this->hidden), array_flip($this->guarded), array_flip($this->getDates()));

		if( count($this->visible) > 0 )
		{
			$columns = array_intersect_key($columns, array_flip($this->visible));
		}

		return $columns;
	}

	/**
	 * Get only visible columns.
	 *
	 * @return array
	 */
	public function getVisibleColumns()
	{
		if( count($this->visible) > 0 )
		{
			return array_intersect_key($this->getColumns(), array_flip($this->visible));
		}

		$columns = array_diff_key($this->getColumns(), array_flip($this->hidden));

		return $columns;
	}

	/**
	 * Get columns which are file uploads.
	 *
	 * @return array
	 */
	public function getFileColumns()
	{
		if( count($this->file_columns) > 0 )
		{
			return $this->file_columns;
		}

		return array();
	}

	/**
	 * Get validation rules.
	 *
	 * @return array
	 */
	public function getRules()
	{
		// Generate default validator rules
		foreach( $this->getEditableColumns() as $name => $column )
		{
			if( ! $column->primary )
			{
				$rules = array();

				// If it's not nullable and not a boolean
				// Booleans are checkboxes so they should never be "required"
				if( $column->required && $column->type != 'boolean' &&
					in_array($name, array_keys($this->getEditableColumns())) )
				{
					$rules[] = 'required';
				}
				// If the column contains "email"
				if( $column->is_email )
				{
					$rules[] = 'email';
				}
				if( $column->unique )
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

	/**
	 * Get table name statically
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return with(new static)->getTable();
	}

}
