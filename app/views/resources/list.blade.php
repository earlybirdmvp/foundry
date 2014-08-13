@extends('layout')

@section('content')

@if ( count($resources) > 0 )

<a href="{{ URL::action($resource_class.'@create') }}">Create</a>

<table class="resource-table" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
@foreach ( $columns as $column )
	@if ( ! in_array($column['name'], $hidden_columns ) )
		<th>{{{ $column['label'] or $column['name'] }}}</th>
	@endif
@endforeach
</tr>

@foreach ( $resources as $resource )
<tr>
@foreach ( $columns as $column )

	@if ( ! in_array($column['name'], $hidden_columns ) )
		@if ( $resource->$column['name'] !== NULL )

			@if ( $column['primary'] || $column['unique'] )
				<td><a href="{{ URL::action($resource_class.'@edit', $resource->id) }}">{{ $resource->$column['name'] }}</a></td>
			@elseif ( $relations[$column['name']] )
				<td>{{{ $resource->$column['relationship']->name }}}</td>
			@else
				<td>{{{ $resource->$column['name'] }}}</td>
			@endif
		@else
			<td><i>NULL</i></td>
		@endif
	@endif

@endforeach
</tr>
@endforeach

</table>

@endif

@stop
