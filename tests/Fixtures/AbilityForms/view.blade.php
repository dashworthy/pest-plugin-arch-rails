@can('blade_can')
@endcan
@cannot("blade_cannot")
@endcannot
@canany(['blade_any_one', 'blade_any_two'])
@endcanany
{{ Gate::allows('blade_gate') ? 'yes' : 'no' }}
