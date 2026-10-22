<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\Forms;

use Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http\NotAGate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Gate as Permissions;

/**
 * Mentions Gate::allows('docblock_ability') without checking it.
 */
class Checks
{
    public function handle(object $user, object $route): void
    {
        // Gate::allows('commented_ability');
        NotAGate::allows('lookalike_ability');
        Permissions::denies('denies_ability');
        Permissions::check('check_ability');
        Permissions::authorize('authorize_ability');
        Permissions::inspect('inspect_ability');
        Permissions::any(['any_one', 'any_two']);
        Permissions::none(['none_ability']);
        Gate::allows(/* why */ 'fqcn_ability');
        $user->can('user_can');
        $user->cannot('user_cannot');
        auth()->user()->can('auth_user_can');
        $this->user()->can('this_user_can');
        $route->middleware('can:middleware_ability');
        $route->can('route_can');
    }
}
