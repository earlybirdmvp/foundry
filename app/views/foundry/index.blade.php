@extends('layout')

@section('content')

@if ( count($resources) > 0 )

<a href="{{ URL::action($foundry_class.'@create') }}">Create</a>

<table class="resource-table" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
@foreach ( $columns as $name => $column )
	<th>{{{ $column['label'] or $name }}}</th>
@endforeach
</tr>

@foreach ( $resources as $resource )
<tr>
@foreach ( $columns as $name => $column )

	@if ( $resource->$name !== NULL )

		@if ( $column['primary'] || $column['unique'] )
			<td><a href="{{ URL::action($foundry_class.'@edit', $resource->id) }}">{{ $resource->$name }}</a></td>
		@elseif ( $relations[$name] )
			<td>
			@if ( $resource->$column['relationship']->foundry_list_value )
				{{{ $resource->$column['relationship']->foundry_list_value }}}
			@elseif ( $resource->$column['relationship']->foundry_value )
				{{{ $resource->$column['relationship']->foundry_value }}}
			@elseif ( $resource->$column['relationship']->name )
				{{{ $resource->$column['relationship']->name }}}
			@else
				{{{ $resource->$column['relationship']->id }}}
			@endif
			</td>
		@else
			<td>{{{ $resource->$name }}}</td>
		@endif
	@else
		<td><i>NULL</i></td>
	@endif

@endforeach
</tr>
@endforeach

</table>

@endif

{{ $resources->links() }}

@stop
