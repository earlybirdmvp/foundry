<?php

class BaseController extends Controller
{

	protected $model;
	protected $table;

	protected $indexes = array();
	protected $columns = array();

	protected $create_rules = array();
	protected $edit_rules = array();

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
			foreach( $this->indexes as $index ) {
				if( in_array($raw->getName(), $index['columns']) ) {
					$matched_index = $index;
					break;
				}
			}

			$this->columns[] = [
				'name'    => $raw->getName(),
				'label'   => ( $raw->getComment() ? $raw->getComment() : NULL ),
				'type'    => $raw->getType()->getName(),
				'default' => $raw->getDefault(),
				'length'  => $raw->getLength(),
				'nullable' => $raw->getNotnull() ? false : true,

				'primary' => $matched_index['primary'],
				'unique'  => $matched_index['unique'],
			];
		}

		// Generate default validator rules
		foreach( $this->columns as $column )
		{
			if( ! $column['primary'] ) {
				$create_rules = $edit_rules = array();

				// If it's not nullable and not a boolean
				// Booleans are checkboxes so they should never be "required"
				if( ! $column['nullable'] && $column['type'] != 'boolean' ) {
					$create_rules[] = 'required';
					$edit_rules[] = 'required';
				}

				// If the column contains "email"
				if( strstr($column['name'], 'email') ) {
					$rules[] = 'email';
					$edit_rules[] = 'email';
				}

				// Unique is only used for creating
				if( $column['unique'] ) {
					$create_rules[] = 'unique:'.$this->table;
				}

				if( count($create_rules) > 0 ) {
					$this->create_rules[$column['name']] = implode('|', $create_rules);
				}
				if( count($edit_rules) > 0 ) {
					$this->edit_rules[$column['name']] = implode('|', $edit_rules);
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
		$resources = call_user_func([$this->model, 'all']);

		return View::make('resources.list')
			->with('resources', $resources)
			->with('columns', $this->columns)
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

		return View::make('resources.edit')
			->with('resource', $resource)
			->with('columns', $this->columns)
			->with('hidden_columns', $resource->getHidden());
	}

	/**
	 * Store resource
	 */
	public function store()
	{
		$id = Input::get('id');

		// Determine if editing or creating
		if( $id ) {
			$resource = call_user_func([$this->model, 'findOrFail'], $id);
			$rules = $this->edit_rules;
		}
		else {
			$resource = new $this->model();
			$rules = $this->create_rules;
		}

		// Run validator on create / edit rules
		$validator = Validator::make(Input::all(), $rules);

		if( $validator->fails() )
		{
			return Redirect::action(get_called_class().'@edit', ['id' => $id])
				->withErrors($validator)
				->withInput();
		}
		else {
			foreach( $this->columns as $column ) {

				// Update columns. Only those which have input, and excluding hidden & primary key
				if( Input::has($column['name']) &&
					! in_array($column['name'], $resource->getHidden()) &&
					! $column['primary'] ) {

					$value = Input::get($column['name']);
					$resource->$column['name'] = $value;
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

		return View::make('resources.edit')
			->with('resource', $resource)
			->with('columns', $this->columns)
			->with('hidden_columns', $resource->getHidden());
	}

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

}
