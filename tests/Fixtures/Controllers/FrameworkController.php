<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

final class FrameworkController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function store(ValidatingRequest $request): JsonResource
    {
        return new JsonResource($request);
    }
}
