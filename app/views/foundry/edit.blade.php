@extends('layout')

@section('content')

<a href="{{ URL::action($resource_class.'@index') }}">View All</a>

@if ( count($columns) > 0 )

<form class="resource-form" method="post" action="{{ URL::action($resource_class.'@store') }}">
<div>

@foreach ( $columns as $column )

@if ( ! in_array($column['name'], $hidden_columns ) )
	<div class="resource-column">
		<label>{{{ $column['label'] or $column['name'] }}}</label>

	@if ( $column['primary'] )
		<input type="hidden" name="{{ $column['name'] }}" value="{{ $resource->$column['name'] }}">
		<span>{{{ $resource->$column['name'] }}}</span>
	@elseif ( $resource->isGuarded($column['name']) || 
		$column['name'] == $resource->getCreatedAtColumn() ||
		$column['name'] == $resource->getUpdatedAtColumn() )
		<span>{{{ $resource->$column['name'] }}}</span>
	@else
		@if ( $relations[$column['name']] )
			{{ Form::select($column['name'], $relations[$column['name']]['options'], $resource->$column['name']) }}
		@elseif ( $column['is_email'] )

			{{ Form::email($column['name'], $resource->$column['name'], [
				'maxlength' => $column['length']
			]) }}

		@elseif ( $column['type'] == 'string' ||
			$column['type'] == 'integer' || 
			$column['type'] == 'decimal' ||
			$column['type'] == 'bigint' )

			{{ Form::text($column['name'], $resource->$column['name'], [
				'maxlength' => $column['length']
			]) }}

		@elseif ( $column['type'] == 'date' )

			{{ Form::text($column['name'].'[year]', substr($resource->$column['name'], 0, 4), ['maxlength' => 4]) }}
			{{ Form::selectMonth($column['name'].'[month]', substr($resource->$column['name'], 5, 2)) }}
			{{ Form::selectRange($column['name'].'[day]', 1, 31, substr($resource->$column['name'], 8, 2)) }}

		@elseif ( $column['type'] == 'boolean' )

			{{ Form::checkbox($column['name'], 1, $resource->$column['name']) }}

		@else

			<span>Unknown type: {{{ $column['type'] }}}</span>

		@endif
	@endif

		{{ $errors->first($column['name'], '<span class="error">:message</div>') }}

	</div>
@endif

@endforeach

	<div class="resource-column">
		<input type="submit" value="Save Node">
	</div>

</div>
</form>

@endif

@stop
