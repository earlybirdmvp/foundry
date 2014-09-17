<a class="btn btn-default" href="{{ URL::action($routes['create']) }}">Create</a>

@if ( count($resources) > 0 )

<table class="table table-striped table-hover" cellspacing="0" cellpadding="0" border="0" width="100%">
<thead>
<tr>
@foreach ( $columns as $name => $column )
	<th>{{{ $column->label or $name }}}</th>
@endforeach
</tr>
</thead>

<tbody>
@foreach ( $resources as $resource )
<tr>
@foreach ( $columns as $name => $column )

	@if ( $resource->$name !== NULL )

		@if ( $column->primary || $column->unique )
			<td><a href="{{ URL::action($routes['edit'], $resource->id) }}">{{ $resource->$name }}</a></td>
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
</tbody>

</table>

@endif

{{ $resources->links() }}
