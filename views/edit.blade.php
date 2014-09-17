<a class="btn btn-default" href="{{ URL::action($foundry_class.'@index') }}">View All</a>

@if ( count($columns) > 0 )

<form class="form-horizontal" method="post" action="{{ URL::action($foundry_class.'@store') }}" enctype="multipart/form-data">
<div>

@foreach ( $columns as $name => $column )

<div class="form-group{{ $errors->has($name) ? ' has-error has-feedback' : '' }}">
@if ( $column->primary )

	<label class="col-sm-2 control-label">{{{ $column->label or $name }}}</label>

	<div class="col-sm-5">
		{{ Form::hidden($name, $resource->$name) }}

		<p class="form-control-static">{{{ $resource->$name }}}</p>
	</div>

@elseif ( $resource->isGuarded($name) ||
	$name == $resource->getCreatedAtColumn() ||
	$name == $resource->getUpdatedAtColumn() )

	<label class="col-sm-2 control-label">{{{ $column->label or $name }}}</label>

	<div class="col-sm-5">
		<p class="form-control-static">{{{ $resource->$name }}}</p>
	</div>

@else

	<label class="col-sm-2 control-label">{{{ $column->label or $name }}}{{ $column->required && $column->type != 'boolean' ? ' *' : '' }}</label>

	<div class="col-sm-5">
	@if ( $relations[$name] )

		{{ Form::select($name, $relations[$name]->options, $resource->$name, ['class' => 'form-control']) }}

	@elseif ( $column->is_email )

		{{ Form::email($name, $resource->$name, [
			'maxlength' => $column->length,
			'class' => 'form-control',
		]) }}

	@elseif ( $column->type == 'text' )

		{{ Form::textarea($name, $resource->$name, ['class' => 'form-control']) }}

	@elseif ( $column->type == 'password' )

		{{ Form::password($name, ['class' => 'form-control']) }}

	@elseif ( $column->type == 'varchar' ||
		$column->type == 'char' ||
		$column->type == 'int' ||
		$column->type == 'decimal' ||
		$column->type == 'bigint' )

		{{ Form::text($name, $resource->$name, [
			'maxlength' => $column->length,
			'class' => 'form-control',
		]) }}

	@elseif ( $column->type == 'file' )

		{{ Form::file($name, [
			'accept' => 'image/*',
			'class' => 'form-control'
		]) }}

	@elseif ( $column->type == 'date' )

		{{ Form::text($name.'[year]', substr($resource->$name, 0, 4), ['maxlength' => 4]) }}
		{{ Form::selectMonth($name.'[month]', substr($resource->$name, 5, 2)) }}
		{{ Form::selectRange($name.'[day]', 1, 31, substr($resource->$name, 8, 2)) }}

	@elseif ( $column->type == 'boolean' )

		{{ Form::checkbox($name, 1, $resource->$name) }}

	@elseif ( $column->type == 'enum' )

		{{ Form::select($name, $column->options, $resource->$name, ['class' => 'form-control']) }}

	@else

		<p class="form-control-static">Unknown type: {{{ $column->type }}}</p>

	@endif

		@if ( $errors->has($name) )
		<span class="glyphicon glyphicon-remove form-control-feedback"></span>
		@endif

	</div>

	@if ( $errors->has($name) )
	<div class="col-sm-3">
		<p class="form-control-static">{{ $errors->first($name) }}</p>
	</div>
	@endif
@endif

</div>

@endforeach

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-5">
		<input type="submit" value="Save {{ $foundry_model }}" class="btn btn-primary">
	</div>
</div>

</div>
</form>

@endif
