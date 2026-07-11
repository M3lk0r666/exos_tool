<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogApiController extends Controller
{
    public function clients(): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        return response()->json(
            Client::withCount('devices')->orderBy('name')
                ->get(['id', 'name', 'contact_name', 'contact_email'])
        );
    }

    public function devices(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Device::class);

        return response()->json(
            Device::with('client:id,name')
                ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
                ->orderBy('sysname')
                ->get(['id', 'client_id', 'sysname', 'alias', 'model', 'serial_number', 'system_mac', 'is_stack', 'site', 'criticality'])
        );
    }
}
