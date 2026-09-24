<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403);

        return Inertia::render('devices/index', [
            'devices' => Device::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->get(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'photos', 'status'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'status']),
                        'photo_url' => ! empty($device->photos) ? route('devices.photo', $device) : null,
                    ];
                }),
        ]);
    }

    public function photo(Request $request, Device $device): StreamedResponse
    {
        abort_unless($request->user()->role === 'customer' && $device->user_id === $request->user()->id, 404);

        $path = $device->photos[0] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403, 'Alleen klanten kunnen apparaten aanmelden.');

        return Inertia::render('devices/create', ['status' => $request->session()->get('status')]);
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

        return to_route('devices.create')->with('status', 'Het apparaat is succesvol aangemeld.');
    }
}
