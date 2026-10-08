<?php

namespace App\Http\Controllers;

use App\Http\Requests\InspectDeviceRequest;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceTriageRequest;
use App\Models\Device;
use App\Models\User;
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

/**
 * Onderdeel: Verwerkt apparaten, keuringen, winkelweergave en reserveringen; validatie staat in aparte requests.
 * Eisen: TE-06.
 * Bouw: T-31 (code- en mappenstructuur).
 * Geplande controle: T-32.
 */
class DeviceController extends Controller
{
    /**
     * Winkeloverzicht; afgekeurde en nog niet goedgekeurde apparaten worden uitgesloten.
     * Eisen: FE-11, RV-05.
     * Planning: T-18 (ontwerp), T-19 (bouw).
     * Geplande controle: T-20.
     * Gereserveerde, eerder goedgekeurde producten blijven zichtbaar maar zijn niet opnieuw beschikbaar (FE-12).
     */
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

    /**
     * Productdetails en keuringsrapport; alleen klanten kunnen andermans beschikbare apparaat reserveren.
     * Eisen: FE-11, FE-12, FE-13, RV-02, RV-05.
     * Planning: T-18/T-19 (winkel), T-21/T-22 (reserveren), T-24/T-25 (roltoegang), T-34 (zelfreservering).
     * Geplande controle: T-20, T-23, T-26.
     */
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
            'isOwnDevice' => (int) $device->user_id === (int) $request->user()->id,
            'canReserve' => $request->user()->role === 'customer'
                && (int) $device->user_id !== (int) $request->user()->id
                && $device->status === 'goedgekeurd',
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Checkout voor het bevestigen van een reservering; blokkeert eigen en al gereserveerde apparaten.
     * Eisen: FE-12, FE-13, RV-02, RV-07.
     * Planning: T-21 (ontwerp), T-22 (bouw), T-25 (roltoegang), T-34 (zelfreservering).
     * Geplande controle: T-23, T-26.
     * De checkout is een betaal-demo; echte betalingen vallen buiten de MVP-afbakening.
     */
    public function checkout(Request $request, Device $device): Response|RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        if ((int) $device->user_id === (int) $request->user()->id) {
            return to_route('shop.show', $device)->with('status', 'Je kunt je eigen apparaat niet reserveren.');
        }

        if ($device->status === 'gereserveerd') {
            return to_route('shop.show', $device)->with('status', 'Dit product is al gereserveerd.');
        }

        return Inertia::render('devices/checkout', [
            'device' => $device->only(['id', 'brand', 'model', 'asking_price']),
        ]);
    }

    /**
     * Reservering atomair vastleggen, zodat hetzelfde product niet door twee klanten wordt gereserveerd.
     * Eisen: FE-12, FE-13, RV-02, RV-07.
     * Planning: T-22 (bouw reserveren), T-25 (roltoegang), T-34 (zelfreservering blokkeren).
     * Geplande controle: T-23, T-26.
     */
    public function reserve(Request $request, Device $device): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless(in_array($device->status, ['goedgekeurd', 'gereserveerd'], true), 404);

        if ((int) $device->user_id === (int) $request->user()->id) {
            return to_route('shop.show', $device)->with('status', 'Je kunt je eigen apparaat niet reserveren.');
        }

        $reserved = Device::query()
            ->whereKey($device->id)
            ->where('user_id', '!=', $request->user()->id)
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

    /**
     * Productfoto alleen beschikbaar stellen als het apparaat eerder is goedgekeurd.
     * Eisen: FE-11, RV-05.
     * Planning: T-19 (bouw winkelweergave).
     * Geplande controle: T-20.
     */
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

    /**
     * Keurmeester toont aangemelde apparaten, geordend op prioriteit en aanmelddatum, met toewijzing.
     * Eisen: FE-06, RV-02.
     * Planning: T-12 (ontwerp), T-13 (bouw overzicht).
     * Geplande controle: T-14.
     * Prioriteren en toewijzen: projectbriefing hoofdstuk 4; geen apart eis- of taaknummer in de planning.
     */
    public function inspectorIndex(Request $request): Response
    {
        return Inertia::render('devices/inspector', [
            'status' => $request->session()->get('status'),
            'inspectors' => User::query()->where('role', 'inspector')->orderBy('name')->get(['id', 'name']),
            'devices' => Device::query()
                ->whereIn('status', ['in behandeling', 'onderhoud nodig', 'goedgekeurd', 'afgekeurd'])
                ->with('assignedInspector:id,name')
                ->orderByDesc('priority')
                ->oldest('created_at')
                ->orderBy('id')
                ->get(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'status', 'created_at', 'photos', 'priority', 'assigned_to_user_id'])
                ->map(function (Device $device): array {
                    return [
                        ...$device->only(['id', 'type', 'brand', 'model', 'serial_number', 'condition', 'status', 'created_at', 'priority', 'assigned_to_user_id']),
                        'assigned_inspector_name' => $device->assignedInspector?->name,
                        'photo_url' => ! empty($device->photos) ? route('devices.photo', $device) : null,
                    ];
                }),
        ]);
    }

    /**
     * Gevalideerde prioriteit en toewijzing opslaan; gereserveerde apparaten niet opnieuw toewijzen.
     * Eisen: FE-06, FE-13, RV-02, RV-07, TE-04.
     * Planning: T-13 (bouw overzicht), T-25 (roltoegang), T-29 (invoercontrole).
     * Geplande controle: T-14, T-26, T-30.
     * Prioriteren en toewijzen: projectbriefing hoofdstuk 4; geen apart eis- of taaknummer in de planning.
     */
    public function updateTriage(UpdateDeviceTriageRequest $request, Device $device): RedirectResponse
    {
        DB::transaction(function () use ($request, $device): void {
            $lockedDevice = Device::query()->lockForUpdate()->findOrFail($device->id);
            if ($lockedDevice->status === 'gereserveerd') {
                throw ValidationException::withMessages(['priority' => 'Dit apparaat is inmiddels gereserveerd en kan niet meer worden toegewezen.']);
            }

            $lockedDevice->priority = (int) $request->validated('priority');
            $lockedDevice->assigned_to_user_id = $request->validated('assigned_to_user_id');
            $lockedDevice->save();
        });

        return to_route('inspector.devices.index')->with('status', 'De prioriteit en toewijzing zijn opgeslagen.');
    }

    /**
     * Geselecteerd apparaat met verkopergegevens en eerdere checklist openen voor keuring.
     * Eisen: FE-06, FE-07, RV-02, RV-07.
     * Planning: T-12 (ontwerp), T-13 (bouw overzicht en checklist).
     * Geplande controle: T-14.
     */
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

    /**
     * Checklist, keuringsopmerkingen, keurmeester, datum en status betrouwbaar samen opslaan.
     * Eisen: FE-07, FE-08, FE-09, FE-10, RV-04, RV-05, RV-07, TE-04.
     * Planning: T-13 (checklist), T-15/T-16 (ontwerp/bouw keuringsstatussen), T-29 (invoercontrole).
     * Geplande controle: T-14, T-17, T-30.
     * FE-08: goedgekeurd; FE-09: afgekeurd; FE-10: onderhoud nodig. Alleen goedgekeurd komt beschikbaar in de winkel.
     */
    public function updateInspection(InspectDeviceRequest $request, Device $device): RedirectResponse
    {
        try {
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
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['status' => 'Er bestaat inmiddels een ander actief apparaat met dit serienummer. Dit apparaat kan alleen afgekeurd blijven.']);
        }

        return to_route('inspector.devices.index')->with('status', 'De keuring is opgeslagen. Het apparaat is '.$request->validated('status').'.');
    }

    /**
     * Alleen eigen apparaten met huidige status en keuringsopmerkingen tonen aan de klant.
     * Eisen: FE-04, FE-05, FE-13, RV-02.
     * Planning: T-09 (ontwerp), T-10 (bouw status en opmerkingen), T-25 (roltoegang).
     * Geplande controle: T-11, T-26.
     */
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

    /**
     * Alleen reserveringen van de ingelogde klant ophalen.
     * Eisen: FE-12, FE-13, RV-02.
     * Planning: T-21 (ontwerp), T-22 (bouw reserveren), T-25 (roltoegang).
     * Geplande controle: T-23, T-26.
     */
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

    /**
     * Privaat opgeslagen apparaatfoto's alleen aan de eigenaar of een keurmeester leveren.
     * Eisen: FE-02, FE-06, FE-13, RV-02, RV-05.
     * Planning: T-07 (aanmelding), T-13 (keuring), T-25 (roltoegang).
     * Geplande controle: T-08, T-14, T-26.
     */
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

    /**
     * Aanmeldformulier uitsluitend voor klanten openen.
     * Eisen: FE-02, FE-03, FE-13, RV-02.
     * Planning: T-06 (ontwerp), T-07 (bouw aanmelding), T-25 (roltoegang).
     * Geplande controle: T-08, T-26.
     */
    public function create(Request $request): Response
    {
        abort_unless($request->user()->role === 'customer', 403, 'Alleen klanten kunnen apparaten aanmelden.');

        return Inertia::render('devices/create');
    }

    /**
     * Gevalideerde apparaatgegevens en foto's opslaan met de ingelogde klant als eigenaar.
     * Eisen: FE-02, FE-03, RV-04, RV-07, TE-04.
     * Planning: T-07 (bouw aanmelding), T-29 (invoercontrole en verwerking).
     * Geplande controle: T-08, T-30.
     * Dubbele actieve serienummers geven een veldmelding; mislukte opslag ruimt reeds opgeslagen foto's op.
     */
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
                throw ValidationException::withMessages(['serial_number' => 'Dit serienummer hoort al bij een actief apparaat. Controleer het nummer of neem contact met ons op.']);
            }
            throw $exception;
        }

        return to_route('shop.index')->with('status', 'Het apparaat is succesvol aangemeld.');
    }
}
