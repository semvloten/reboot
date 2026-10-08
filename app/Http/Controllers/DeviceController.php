<?php

namespace App\Http\Controllers;

use App\Http\Requests\InspectDeviceRequest;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\Device;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DeviceController extends Controller
{
    public function shop(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'status' => $request->session()->get('status'),
            'devices' => Device::query()
                ->whereIn('status', ['goedgekeurd', 'gereserveerd'])
                ->orderBy('asking_price')
                ->get(['id', 'type', 'brand', 'model', 'condition', 'accessories', 'asking_price', 'status', 'photos'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'type', 'brand', 'model', 'condition', 'accessories', 'asking_price', 'status']),
                        'photo_url' => ! empty($device->photos) ? route('shop.photo', $device) : null,
                    ];
                }),
        ]);
    }

    public function shopShow(Request $request, Device $device): Response
    {
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        return Inertia::render('devices/product', [
            'device' => [
                ...$device->only(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'status', 'inspection', 'inspected_at']),
                'photo_urls' => array_map(
                    fn (int $index): string => route('shop.photo', ['device' => $device, 'index' => $index]),
                    array_keys($device->photos ?? []),
                ),
            ],
            'canReserve' => $request->user()->role === 'customer' && $device->status === 'goedgekeurd',
            'status' => $request->session()->get('status'),
        ]);
    }

    public function checkout(Request $request, Device $device): Response|RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        if ($device->status === 'gereserveerd') {
            return to_route('shop.show', $device)->with('status', 'Dit product is al gereserveerd.');
        }

        return Inertia::render('devices/checkout', [
            'device' => $device->only(['id', 'brand', 'model', 'asking_price']),
        ]);
    }

    public function reserve(Request $request, Device $device): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        $reserved = Device::query()
            ->whereKey($device->id)
            ->where('status', 'goedgekeurd')
            ->whereNull('reserved_by_user_id')
            ->update([
                'status' => 'gereserveerd',
                'reserved_by_user_id' => $request->user()->id,
            ]);

        return to_route('shop.show', $device)->with('status', $reserved
            ? 'Je reservering is bevestigd. Dit product is voor jou gereserveerd.'
            : 'Dit product is niet meer beschikbaar. Er is geen reservering gemaakt.');
    }

    public function shopPhoto(Request $request, Device $device): StreamedResponse
    {
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        $index = filter_var($request->query('index', 0), FILTER_VALIDATE_INT);
        abort_if($index === false || $index < 0, 404);

        $path = $device->photos[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function inspectorIndex(): Response
    {
        return Inertia::render('devices/inspector', [
            'devices' => Device::query()
                ->whereIn('status', ['in behandeling', 'onderhoud nodig', 'goedgekeurd', 'afgekeurd'])
                ->latest('id')
                ->get(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'status', 'created_at', 'photos'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'status', 'created_at']),
                        'photo_url' => ! empty($device->photos) ? route('devices.photo', $device) : null,
                    ];
                }),
        ]);
    }

    public function inspect(Device $device): Response
    {
        abort_if($device->status === 'gereserveerd', 409, 'Een gereserveerd apparaat kan niet opnieuw worden gekeurd.');

        $device->load('user:id,name,email');

        return Inertia::render('devices/inspect', [
            'device' => [
                ...$device->only(['id', 'user_id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'status', 'created_at', 'updated_at', 'inspection', 'inspected_at', 'inspected_by_user_id']),
                'customer_name' => $device->user?->name,
                'customer_email' => $device->user?->email,
                'photo_urls' => array_map(
                    fn (int $index): string => route('devices.photo', ['device' => $device, 'index' => $index]),
                    array_keys($device->photos ?? []),
                ),
            ],
        ]);
    }

    public function updateInspection(InspectDeviceRequest $request, Device $device): RedirectResponse
    {
        DB::transaction(function () use ($request, $device): void {
            $lockedDevice = Device::query()->lockForUpdate()->findOrFail($device->id);
            if ($lockedDevice->status === 'gereserveerd' || $lockedDevice->reserved_by_user_id !== null) {
                throw ValidationException::withMessages(['status' => 'Dit apparaat is inmiddels gereserveerd en kan niet opnieuw worden gekeurd.']);
            }

            $lockedDevice->status = $request->validated('status');
            $lockedDevice->inspection = $request->safe()->except('status');
            $lockedDevice->inspected_by_user_id = $request->user()->id;
            $lockedDevice->inspected_at = now();
            $lockedDevice->save();
        });

        return to_route('shop.index')->with('status', 'De keuring is opgeslagen. Het apparaat is '.$request->validated('status').'.');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403);

        return Inertia::render('devices/index', [
            'devices' => Device::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->get(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'photos', 'status', 'inspection', 'inspected_at'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'status', 'inspection', 'inspected_at']),
                        'photo_url' => ! empty($device->photos) ? route('devices.photo', $device) : null,
                    ];
                }),
        ]);
    }

    public function reservations(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403);

        return Inertia::render('devices/reservations', [
            'reservedDevices' => Device::query()
                ->where('reserved_by_user_id', $request->user()->id)
                ->where('status', 'gereserveerd')
                ->latest('updated_at')
                ->get(['id', 'brand', 'model', 'asking_price', 'photos'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'brand', 'model', 'asking_price']),
                        'photo_url' => ! empty($device->photos) ? route('shop.photo', $device) : null,
                    ];
                }),
        ]);
    }

    public function photo(Request $request, Device $device): StreamedResponse
    {
        abort_unless($request->user()->role === 'inspector' || ($request->user()->role === 'customer' && $device->user_id === $request->user()->id), 404);

        $index = filter_var($request->query('index', 0), FILTER_VALIDATE_INT);
        abort_if($index === false || $index < 0, 404);

        $path = $device->photos[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403, 'Alleen klanten kunnen apparaten aanmelden.');

        return Inertia::render('devices/create');
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photos');
        $paths = [];

        try {
            foreach ($request->file('photos', []) as $photo) {
                $path = $photo->store('devices/'.$request->user()->id, 'local');
                if ($path === false) {
                    throw new RuntimeException('De foto kon niet worden opgeslagen.');
                }
                $paths[] = $path;
            }

            DB::transaction(function () use ($request, $data, $paths): void {
                $device = new Device($data);
                $device->user()->associate($request->user());
                $device->photos = $paths;
                $device->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['serial_number' => 'Dit serienummer is al geregistreerd. Controleer het nummer of neem contact met ons op.']);
            }
            throw $exception;
        }

        return to_route('shop.index')->with('status', 'Het apparaat is succesvol aangemeld.');
    }
}
