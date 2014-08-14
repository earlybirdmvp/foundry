@extends('layout')

@section('content')

<a href="{{ URL::action($foundry_class.'@index') }}">View All</a>

@if ( count($columns) > 0 )

<form class="resource-form" method="post" action="{{ URL::action($foundry_class.'@store') }}">
<div>

@foreach ( $columns as $name => $column )

<div class="resource-column">
	<label>{{{ $column->label or $name }}}</label>

@if ( $column->primary )

	{{ Form::hidden($name, $resource->$name) }}

	<span>{{{ $resource->$name }}}</span>

@elseif ( $resource->isGuarded($name) || 
	$name == $resource->getCreatedAtColumn() ||
	$name == $resource->getUpdatedAtColumn() )

	<span>{{{ $resource->$name }}}</span>

@else
	@if ( $relations[$name] )

		{{ Form::select($name, $relations[$name]->options, $resource->$name) }}

	@elseif ( $column->is_email )

		{{ Form::email($name, $resource->$name, [
			'maxlength' => $column->length
		]) }}

	@elseif ( $column->type == 'text' )

		{{ Form::textarea($name, $resource->$name) }}

	@elseif ( $column->type == 'string' ||
		$column->type == 'integer' || 
		$column->type == 'decimal' ||
		$column->type == 'bigint' )

		{{ Form::text($name, $resource->$name, [
			'maxlength' => $column->length
		]) }}

	@elseif ( $column->type == 'date' )

		{{ Form::text($name.'[year]', substr($resource->$name, 0, 4), ['maxlength' => 4]) }}
		{{ Form::selectMonth($name.'[month]', substr($resource->$name, 5, 2)) }}
		{{ Form::selectRange($name.'[day]', 1, 31, substr($resource->$name, 8, 2)) }}

	@elseif ( $column->type == 'boolean' )

		{{ Form::checkbox($name, 1, $resource->$name) }}

	@else

		<span>Unknown type: {{{ $column->type }}}</span>

	@endif
@endif

	{{ $errors->first($name, '<span class="error">:message</div>') }}

</div>

@endforeach

	<div class="resource-column">
		<input type="submit" value="Save Node">
	</div>

</div>
</form>

@endif

@stop
