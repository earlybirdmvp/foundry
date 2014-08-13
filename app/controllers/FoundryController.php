<?php

class FoundryController extends BaseController
{

	protected $model;
	protected $table;

	protected $indexes = array();
	protected $columns = array();

	protected $rules = array();

	/**
	 * Loads all database columns and indexes for this resource
	 */
	public function __construct()
	{
		$class = get_called_class();

		$this->model = str_replace('Controller', '', $class);
		$this->table = with(new $this->model)->getTable();

		// @todo issue with enums
		/*$schema = DB::getDoctrineSchemaManager();
		$schema->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');*/

		// Fetch all indexes from this table
		$raw_indexes = DB::connection()
			->getDoctrineSchemaManager()
			->listTableIndexes($this->table);

		foreach( $raw_indexes as $raw )
		{
			$this->indexes[] = [
				'name'    => $raw->getName(),
				'columns' => $raw->getColumns(),
				'primary' => $raw->isPrimary(),
				'unique'  => $raw->isUnique(),
			];
		}

		// Fetch all columns from this table
		$raw_columns = DB::connection()
			->getDoctrineSchemaManager()
			->listTableColumns($this->table);

		foreach( $raw_columns as $raw )
		{
			// Check if there is an index on this column
			$matched_index = NULL;
			foreach( $this->indexes as $index )
			{
				if( in_array($raw->getName(), $index['columns']) )
				{
					$matched_index = $index;
					break;
				}
			}

			$this->columns[] = [
				'name'    => $raw->getName(),
				'label'   => ( $raw->getComment() ? $raw->getComment() : NULL ),

				'relationship' => str_replace('_id', '', $raw->getName()),

				'type'     => $raw->getType()->getName(),
				'default'  => $raw->getDefault(),
				'length'   => $raw->getLength(),
				'required' => $raw->getNotnull() ? true : false,

				'primary' => $matched_index['primary'],
				'unique'  => $matched_index['unique'],
			];
		}

		// Generate default validator rules
		foreach( $this->columns as $column )
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
				if( strstr($column['name'], 'email') )
				{
					$rules[] = 'email';
				}
				if( $column['unique'] )
				{
					$rules[] = 'unique:'.$this->table.','.$column['name'];
				}

				if( count($rules) > 0 )
				{
					$this->rules[$column['name']] = $rules;
				}
			}
		}

		View::share('resource_class', $class);
		View::share('resource_model', $this->model);
	}

	/**
	 * List all resources of this type
	 * @todo    pagination
	 *
	 * @return  Response
	 */
	public function index()
	{
		$resources = call_user_func([$this->model, 'paginate'], 10);

		// Try to determine relationships
		foreach( $this->columns as $column )
		{
			if( ends_with($column['name'], '_id') )
			{
				try
				{
					$resources->load($column['relationship']);
					$relations[$column['name']] = true;
				}
				catch( Exception $e ) { }
			}
		}

		return View::make('foundry.list')
			->with('resources', $resources)
			->with('columns', $this->columns)
			->with('relations', $relations)
			->with('hidden_columns', with(new $this->model)->getHidden());
	}

	/**
	 * Edit resource identified by $id
	 *
	 * @param   int   $id
	 * @return  Response
	 */
	public function edit( $id )
	{
		$resource = call_user_func([$this->model, 'findOrFail'], $id);

		$relations = array();

		// Try to determine relationships
		foreach( $this->columns as $column )
		{
			if( ends_with($column['name'], '_id') )
			{
				try
				{
					$resource->load($column['relationship']);
					$relation = $resource->getRelation($column['relationship']);

					// Load all options for this relationship
					$class = get_class($relation);
					$data = $class::all();
					$options = array();

					// Blank option if not required
					if( ! $column['required'] ) {
						$options[0] = '&ndash; Choose &ndash;';
					}
					foreach( $data as $datum ) {
						if( $datum->foundry_edit_value ) {
							$options[$datum->id] = $datum->foundry_edit_value;
						}
						else if( $datum->foundry_value ) {
							$options[$datum->id] = $datum->foundry_value;
						}
						else if( $datum->name ) {
							$options[$datum->id] = $datum->name;
						}
						else {
							$options[$datum->id] = $datum->id;
						}
					}

					$relations[$column['name']] = [
						'class' => $class,
						'options' => $options
					];
				}
				catch( Exception $e ) { }
			}
		}

		return View::make('foundry.edit')
			->with('resource', $resource)
			->with('columns', $this->columns)
			->with('relations', $relations)
			->with('hidden_columns', $resource->getHidden());
	}

	/**
	 * Store resource
	 */
	public function store()
	{
		$id = Input::get('id');

		$rules = $this->rules;

		// Determine if editing or creating
		if( $id )
		{
			$resource = call_user_func([$this->model, 'findOrFail'], $id);

			foreach( $rules as $key => $ruleset )
			{
				for( $i=0; $i<count($ruleset); $i++ )
				{
					if( strstr($ruleset[$i], 'unique') )
					{
						$rules[$key][$i] .= ','.$id;
					}
				}
				$rules[$key] = implode('|', $rules[$key]);
			}
		}
		else
		{
			$resource = new $this->model();
		}

		// Run validator on rules
		$validator = Validator::make(Input::all(), $rules);

		if( $validator->fails() )
		{
			return Redirect::action(get_called_class().'@edit', ['id' => $id])
				->withErrors($validator)
				->withInput();
		}
		else
		{
			foreach( $this->columns as $column )
			{

				// Update columns. Only those which have input, and excluding hidden & primary key
				if( ! in_array($column['name'], $resource->getHidden()) &&
					! $resource->isGuarded($column['name']) &&
			        $column['name'] != $resource->getCreatedAtColumn() &&
			        $column['name'] != $resource->getUpdatedAtColumn() &&
					! $column['primary'] )
				{

					if( Input::has($column['name']) )
					{
						$value = Input::get($column['name']);

						// Additional formatting
						if( $column['type'] == 'date' )
						{
							$value = $value['year'].'-'.$value['month'].'-'.$value['day'];
						}

						$resource->$column['name'] = $value;
					}
					// Data removed
					else
					{
						switch( $column['type'] )
						{
							case 'integer':
							case 'bigint':
							case 'decimal':
							case 'boolean':
								$default = 0;
								break;

							case 'string':
								$default = '';
								break;

							default:
								$default = NULL;
								break;
						}

						// If not required, and not a boolean, default is NULL
						if( ! $column['required'] && 
							$column['type'] != 'boolean' && 
							$column['type'] != 'string' )
						{
							$default = NULL;
						}

						$resource->$column['name'] = $default;
					}
				}

			}

			$resource->save();

			return Redirect::action(get_called_class().'@index');
		}
	}

	/**
	 * Create a new resource
	 *
	 * @return  Response
	 */
	public function create()
	{
		$resource = new $this->model();

		return View::make('foundry.edit')
			->with('resource', $resource)
			->with('columns', $this->columns)
			->with('hidden_columns', $resource->getHidden());
	}

}
