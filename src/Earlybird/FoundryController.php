<?php namespace Earlybird;

class FoundryController extends \BaseController
{

	/**
	 * The model Class that this Controller manages.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Number to show per page
	 *
	 * @var int
	 */
	protected $per_page = 10;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$class = get_class($this);

		\View::share('foundry_class', $class);
		\View::share('foundry_model', $this->model);
	}

	/**
	 * Get the model associated with the controller.
	 *
	 * @return string
	 */
	public function getModel()
	{
		if (isset($this->model)) return $this->model;

		return str_replace('Controller', '', get_class($this));
	}

	/**
	 * Set the model associated with the controller.
	 *
	 * @param  string  $model
	 * @return void
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * List all resources of this type
	 *
	 * @return  Response
	 */
	public function index()
	{
		$model = $this->getModel();
		$resources = $model::paginate($this->per_page);
		$columns = with(new $model)->getVisibleColumns();

		// Try to determine relationships
		// @todo use get_class_methods instead, move to Model
		foreach( $columns as $name => $column )
		{
			if( ends_with($name, '_id') )
			{
				try
				{
					$resources->load($column->relationship);
					$relations[$name] = true;
				}
				catch( \Exception $e ) { }
			}
		}

		return \View::make('foundry::index')
			->with('resources', $resources)
			->with('columns', $columns)
			->with('relations', $relations);
	}

	/**
	 * Edit resource identified by $id
	 *
	 * @param   int   $id
	 * @return  Response
	 */
	public function edit( $id )
	{
		$model = $this->getModel();
		$resource = $model::findOrFail($id);

		$relations = array();

		// Try to determine relationships
		foreach( $resource->getVisibleColumns() as $name => $column )
		{
			if( ends_with($name, '_id') )
			{
				try
				{
					$resource->load($column->relationship);
					$relation = $resource->getRelation($column->relationship);

					// Load all options for this relationship
					$class = get_class($relation);
					$data = $class::all();
					$options = array();

					// Blank option if not required
					if( ! $column->required ) {
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

					$rel = new \stdClass();
					$rel->class = $class;
					$rel->options = $options;

					$relations[$name] = $rel;
				}
				catch( \Exception $e ) { }
			}
		}

		return \View::make('foundry::edit')
			->with('resource', $resource)
			->with('columns', $resource->getVisibleColumns())
			->with('relations', $relations);
	}

	/**
	 * Store resource
	 */
	public function store()
	{
		$model = $this->getModel();
		$id = \Input::get('id');

		// Determine if editing or creating
		if( $id )
		{
			$resource = $model::findOrFail($id);
		}
		else
		{
			$resource = new $model;
		}

		// Run validator on rules
		$validator = \Validator::make(\Input::all(), $resource->getRules());

		if( $validator->fails() )
		{
			return \Redirect::action(get_called_class().'@edit', ['id' => $id])
				->withErrors($validator)
				->withInput();
		}
		else
		{
			foreach( $resource->getEditableColumns() as $name => $column )
			{
				if( \Input::has($name) )
				{
					$value = \Input::get($name);

					// Additional formatting
					if( $column->type == 'date' )
					{
						$value = $value['year'].'-'.$value['month'].'-'.$value['day'];
					}

					$resource->$name = $value;
				}
				// Data removed
				else
				{
					switch( $column->type )
					{
						case 'integer':
						case 'bigint':
						case 'decimal':
						case 'boolean':
							$default = 0;
							break;

						case 'string':
						case 'text':
							$default = '';
							break;

						default:
							$default = NULL;
							break;
					}

					$not_nullable = ['boolean', 'string', 'text'];

					// If not required
					if( ! $column->required && ! in_array($column->type, $not_nullable) )
					{
						$default = NULL;
					}

					$resource->$name = $default;
				}

			}

			$resource->save();

			return \Redirect::action(get_called_class().'@index');
		}
	}

	/**
	 * Create a new resource
	 *
	 * @return  Response
	 */
	public function create()
	{
		$model = $this->getModel();
		$resource = new $model;

		return \View::make('foundry::edit')
			->with('resource', $resource)
			->with('columns', $resource->getVisibleColumns());
	}

}
