@extends('layout')

@section('content')

<a href="{{ URL::action($foundry_class.'@create') }}">Create</a>

@if ( count($resources) > 0 )

<table class="resource-table" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
@foreach ( $columns as $name => $column )
	<th>{{{ $column->label or $name }}}</th>
@endforeach
</tr>

@foreach ( $resources as $resource )
<tr>
@foreach ( $columns as $name => $column )

	@if ( $resource->$name !== NULL )

		@if ( $column->primary || $column->unique )
			<td><a href="{{ URL::action($foundry_class.'@edit', $resource->id) }}">{{ $resource->$name }}</a></td>
		@elseif ( $relations[$name] )
			<td>
			<?php $rel = $column->relationship; ?>
			@if ( $resource->$rel->foundry_list_value )
				{{{ $resource->$rel->foundry_list_value }}}
			@elseif ( $resource->$rel->foundry_value )
				{{{ $resource->$rel->foundry_value }}}
			@elseif ( $resource->$rel->name )
				{{{ $resource->$rel->name }}}
			@else
				{{{ $resource->$rel->id }}}
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
