<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Package;
use App\Models\PackagePrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PackageAreaController extends Controller
{
    /**
     * Display listing and management for Packages & Areas.
     */
    public function index(): Response
    {
        Gate::authorize('packages.view');

        $packages = Package::with(['currentPrice', 'prices' => function ($q) {
            $q->orderByDesc('effective_from');
        }])
        ->withCount(['connections'])
        ->orderBy('speed_mbps')
        ->get();

        $areas = Area::with(['parent'])
            ->withCount(['customers', 'connections'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'areas' => $areas,
        ]);
    }

    /**
     * Store a newly created Package.
     */
    public function storePackage(Request $request): RedirectResponse
    {
        Gate::authorize('packages.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:packages,code',
            'speed_mbps' => 'required|integer|min:1',
            'recommended_devices' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'features' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated) {
            $package = Package::create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'speed_mbps' => $validated['speed_mbps'],
                'recommended_devices' => $validated['recommended_devices'] ?? null,
                'description' => $validated['description'] ?? null,
                'features' => $validated['features'] ?? [],
                'status' => $validated['status'],
            ]);

            PackagePrice::create([
                'package_id' => $package->id,
                'price' => $validated['price'],
                'validity_days' => $validated['validity_days'],
                'effective_from' => now(),
                'status' => 'active',
            ]);

            AuditLog::log('package.created', 'packages', $package, null, [
                'name' => $package->name,
                'code' => $package->code,
                'speed_mbps' => $package->speed_mbps,
                'price' => $validated['price'],
            ]);
        });

        return back()->with('success', 'Package created successfully.');
    }

    /**
     * Update an existing Package and its pricing version.
     */
    public function updatePackage(Request $request, Package $package): RedirectResponse
    {
        Gate::authorize('packages.update');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:packages,code,' . $package->id,
            'speed_mbps' => 'required|integer|min:1',
            'recommended_devices' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'features' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($package, $validated) {
            $oldValues = $package->toArray();
            $package->load('currentPrice');
            $currentPrice = $package->currentPrice;

            $package->update([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'speed_mbps' => $validated['speed_mbps'],
                'recommended_devices' => $validated['recommended_devices'] ?? null,
                'description' => $validated['description'] ?? null,
                'features' => $validated['features'] ?? [],
                'status' => $validated['status'],
            ]);

            // If price or validity changed, supersede old price and insert new version
            $priceChanged = !$currentPrice || 
                (float)$currentPrice->price !== (float)$validated['price'] || 
                (int)$currentPrice->validity_days !== (int)$validated['validity_days'];

            if ($priceChanged) {
                if ($currentPrice) {
                    $currentPrice->update([
                        'status' => 'superseded',
                        'effective_to' => now(),
                    ]);
                }

                PackagePrice::create([
                    'package_id' => $package->id,
                    'price' => $validated['price'],
                    'validity_days' => $validated['validity_days'],
                    'effective_from' => now(),
                    'status' => 'active',
                ]);
            }

            AuditLog::log('package.updated', 'packages', $package, $oldValues, [
                'name' => $package->name,
                'code' => $package->code,
                'speed_mbps' => $package->speed_mbps,
                'price' => $validated['price'],
                'price_changed' => $priceChanged,
            ]);
        });

        return back()->with('success', 'Package updated successfully.');
    }

    /**
     * Delete/Archive a package.
     */
    public function destroyPackage(Package $package): RedirectResponse
    {
        Gate::authorize('packages.archive');

        $activeConnections = $package->connections()->count();
        if ($activeConnections > 0) {
            return back()->with('error', "Cannot delete package: {$activeConnections} customer connection(s) currently use this package.");
        }

        AuditLog::log('package.deleted', 'packages', $package, $package->toArray(), null);
        $package->delete();

        return back()->with('success', 'Package deleted successfully.');
    }

    /**
     * Store a new Area.
     */
    public function storeArea(Request $request): RedirectResponse
    {
        Gate::authorize('areas.create');

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:areas,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:areas,code',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $area = Area::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        AuditLog::log('area.created', 'areas', $area, null, $area->toArray());

        return back()->with('success', 'Coverage Area created successfully.');
    }

    /**
     * Update an existing Area.
     */
    public function updateArea(Request $request, Area $area): RedirectResponse
    {
        Gate::authorize('areas.update');

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:areas,id|not_in:' . $area->id,
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:areas,code,' . $area->id,
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $oldValues = $area->toArray();
        $area->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        AuditLog::log('area.updated', 'areas', $area, $oldValues, $area->toArray());

        return back()->with('success', 'Coverage Area updated successfully.');
    }

    /**
     * Delete an Area.
     */
    public function destroyArea(Area $area): RedirectResponse
    {
        Gate::authorize('areas.archive');

        $customerCount = $area->customers()->count();
        if ($customerCount > 0) {
            return back()->with('error', "Cannot delete area: {$customerCount} customer(s) are registered in this area.");
        }

        $childrenCount = $area->children()->count();
        if ($childrenCount > 0) {
            return back()->with('error', "Cannot delete area: {$childrenCount} sub-zone(s) belong to this area.");
        }

        AuditLog::log('area.deleted', 'areas', $area, $area->toArray(), null);
        $area->delete();

        return back()->with('success', 'Coverage Area deleted successfully.');
    }
}
