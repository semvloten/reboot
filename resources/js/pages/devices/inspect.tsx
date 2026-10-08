import InputError from '@/components/input-error';
import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ClipboardCheck, ImageOff, LoaderCircle, ZoomIn } from 'lucide-react';
import { type FormEventHandler } from 'react';

// Koppelt de opgeslagen checklistvelden aan de teksten die de keurmeester ziet.
const checks = [
    ['works', 'Apparaat werkt volledig'],
    ['accessories_work', 'Accessoires werken goed'],
    ['presentable', 'Apparaat is in vertoonbare staat'],
    ['plugs_present', 'Alle benodigde stekkers zijn aanwezig'],
    ['ports_work', 'Aansluitingen werken goed'],
    ['reset_done', 'Fabrieksreset is uitgevoerd'],
    ['screen_work', 'Scherm werkt goed'],
    ['battery_work', 'Batterij werkt goed'],
] as const;

type CheckKey = (typeof checks)[number][0];
type InspectionForm = Record<CheckKey, boolean> & {
    status: string;
    notes: string;
    battery_percentage: string;
    video_port: string;
    port_types: string;
};
type Device = {
    id: number;
    brand: string;
    model: string;
    type: 'console' | 'telefoon' | 'laptops';
    serial_number: string;
    condition: string;
    accessories: string | null;
    asking_price: string;
    status: string;
    created_at: string;
    photo_url: string | null;
    inspection: Partial<InspectionForm> | null;
};

export default function InspectDevice({ device, status }: { device: Device; status?: string }) {
    // Vult een bestaande keuring opnieuw in; ontbrekende controles beginnen op false.
    const previous = device.inspection;
    const { data, setData, patch, processing, errors, hasErrors } = useForm<InspectionForm>({
        works: previous?.works ?? false,
        accessories_work: previous?.accessories_work ?? false,
        presentable: previous?.presentable ?? false,
        plugs_present: previous?.plugs_present ?? false,
        ports_work: previous?.ports_work ?? false,
        reset_done: previous?.reset_done ?? false,
        screen_work: previous?.screen_work ?? false,
        battery_work: previous?.battery_work ?? false,
        battery_percentage: String(previous?.battery_percentage ?? ''),
        video_port: previous?.video_port ?? '',
        port_types: previous?.port_types ?? '',
        notes: previous?.notes ?? '',
        status: previous ? device.status : '',
    });
    // Bij consoles vervallen scherm en batterij; tel alleen de zichtbare controles.
    const visibleChecks = checks.filter(([key]) => device.type !== 'console' || (key !== 'screen_work' && key !== 'battery_work'));
    const completed = visibleChecks.filter(([key]) => data[key]).length;
    const fieldClass =
        'min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-[#111827] focus-visible:outline-2 focus-visible:outline-emerald-600';
    // Slaat de checklist, opmerkingen en gekozen keuringsstatus op voor dit apparaat.
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('inspector.devices.update', device.id), { preserveScroll: true });
    };

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Apparaat keuren | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-5xl px-4 py-8 sm:p-8">
                <Link
                    href={route('inspector.devices.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" /> Apparaatoverzicht
                </Link>
                <h1 className="mt-5 text-3xl font-bold">Apparaat keuren</h1>
                <p className="mt-2 text-slate-600">Controleer het apparaat en leg je beoordeling vast.</p>
                {status && (
                    <div role="status" className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                        {status}
                    </div>
                )}
                <div className="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_1.3fr]">
                    <section aria-labelledby="device-title" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <p className="text-sm font-medium text-emerald-700">Keuring · #{device.id}</p>
                        <h2 id="device-title" className="mt-1 text-xl font-bold">
                            {device.brand} {device.model}
                        </h2>
                        {device.photo_url ? (
                            <Dialog>
                                <DialogTrigger asChild>
                                    <button
                                        type="button"
                                        className="mt-5 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-left focus-visible:outline-2 focus-visible:outline-emerald-600"
                                    >
                                        <img src={device.photo_url} alt={device.brand + ' ' + device.model} className="h-52 w-full object-contain" />
                                        <span className="mt-3 flex items-center justify-center gap-2 text-sm text-slate-600">
                                            <ZoomIn className="size-4" aria-hidden="true" /> Klik op de foto om in te zoomen
                                        </span>
                                    </button>
                                </DialogTrigger>
                                <DialogContent className="max-w-3xl">
                                    <DialogTitle>
                                        {device.brand} {device.model}
                                    </DialogTitle>
                                    <DialogDescription>Vergrote foto van het aangemelde apparaat.</DialogDescription>
                                    <img
                                        src={device.photo_url}
                                        alt={device.brand + ' ' + device.model}
                                        className="max-h-[75vh] w-full object-contain"
                                    />
                                </DialogContent>
                            </Dialog>
                        ) : (
                            <div className="mt-5 flex h-44 flex-col items-center justify-center gap-2 rounded-xl bg-slate-50 text-slate-500">
                                <ImageOff className="size-8" aria-hidden="true" /> Geen foto beschikbaar
                            </div>
                        )}
                        <dl className="mt-5 space-y-3 text-sm">
                            {[
                                ['Soort', device.type === 'laptops' ? 'Laptop' : device.type === 'telefoon' ? 'Telefoon' : 'Console'],
                                ['Apparaat ID', '#' + device.id],
                                ['Serienummer', device.serial_number],
                                ['Aangemeld op', new Date(device.created_at).toLocaleDateString('nl-NL')],
                                ['Conditie', device.condition],
                                ['Accessoires', device.accessories || 'Geen accessoires opgegeven'],
                                [
                                    'Vraagprijs',
                                    new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(Number(device.asking_price)),
                                ],
                                ['Huidige status', device.status],
                            ].map(([label, value]) => (
                                <div key={label} className="grid grid-cols-[7rem_1fr] gap-3">
                                    <dt className="text-slate-500">{label}</dt>
                                    <dd className="font-medium break-words">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                    <form onSubmit={submit} noValidate className="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div>
                            <h2 className="flex items-center gap-2 text-xl font-bold">
                                <ClipboardCheck className="size-5 text-emerald-600" aria-hidden="true" /> Checklist
                            </h2>
                            <p className="mt-2 text-sm text-slate-500">
                                Vink geslaagde controles aan. Zonder accessoires mag je de accessoirescontrole als geslaagd markeren.
                            </p>
                        </div>
                        {hasErrors && (
                            <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                De keuring is niet opgeslagen. Controleer de gemarkeerde velden hieronder.
                            </p>
                        )}
                        <fieldset disabled={processing} className="space-y-6">
                            <legend className="sr-only">Keuringsresultaten</legend>
                            <div className="space-y-3">
                                {visibleChecks.map(([key, label]) => (
                                    <div key={key}>
                                        <label className="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-medium">
                                            <input
                                                type="checkbox"
                                                checked={data[key]}
                                                onChange={(event) => setData(key, event.target.checked)}
                                                className="size-4 shrink-0 accent-emerald-600"
                                                aria-invalid={!!errors[key]}
                                                aria-describedby={key + '-error'}
                                            />
                                            {label}
                                        </label>
                                        <InputError id={key + '-error'} message={errors[key]} className="mt-1" />
                                    </div>
                                ))}
                                <p className="text-sm text-slate-500">
                                    {completed} van {visibleChecks.length} controles geslaagd.
                                </p>
                                {data.status === 'goedgekeurd' && completed !== visibleChecks.length && (
                                    <p className="text-sm text-red-600">
                                        Een apparaat kan alleen worden goedgekeurd als alle nodige checkboxes zijn geselecteerd.
                                    </p>
                                )}
                            </div>
                            {device.type === 'console' && (
                                <div className="space-y-2">
                                    <Label htmlFor="video_port">Type videopoort *</Label>
                                    <Input
                                        id="video_port"
                                        list="video-ports"
                                        required
                                        maxLength={100}
                                        placeholder="Bijvoorbeeld HDMI"
                                        value={data.video_port}
                                        onChange={(event) => setData('video_port', event.target.value)}
                                        className={fieldClass}
                                        aria-invalid={!!errors.video_port}
                                        aria-describedby="video-port-error"
                                    />
                                    <datalist id="video-ports">
                                        <option value="HDMI" />
                                        <option value="DisplayPort" />
                                        <option value="AV" />
                                        <option value="USB-C" />
                                    </datalist>
                                    <InputError id="video-port-error" message={errors.video_port} />
                                </div>
                            )}
                            {device.type !== 'console' && (
                                <div className="space-y-2">
                                    <Label htmlFor="battery_percentage">Batterijconditie (%) *</Label>
                                    <p className="text-sm text-slate-500">Resterende maximale capaciteit ten opzichte van een nieuwe batterij.</p>
                                    <Input
                                        id="battery_percentage"
                                        type="number"
                                        min={0}
                                        max={100}
                                        step={1}
                                        required
                                        placeholder="Bijvoorbeeld 85"
                                        value={data.battery_percentage}
                                        onChange={(event) => setData('battery_percentage', event.target.value)}
                                        className={fieldClass}
                                        aria-invalid={!!errors.battery_percentage}
                                        aria-describedby="battery-error"
                                    />
                                    <InputError id="battery-error" message={errors.battery_percentage} />
                                </div>
                            )}
                            {device.type === 'laptops' && (
                                <div className="space-y-2">
                                    <Label htmlFor="port_types">Typen aansluitingen *</Label>
                                    <Input
                                        id="port_types"
                                        required
                                        maxLength={255}
                                        placeholder="Bijvoorbeeld USB-A, USB-C, HDMI en audio"
                                        value={data.port_types}
                                        onChange={(event) => setData('port_types', event.target.value)}
                                        className={fieldClass}
                                        aria-invalid={!!errors.port_types}
                                        aria-describedby="ports-error"
                                    />
                                    <InputError id="ports-error" message={errors.port_types} />
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="notes">Opmerkingen / beschrijving *</Label>
                                <textarea
                                    id="notes"
                                    required
                                    maxLength={5000}
                                    rows={5}
                                    placeholder="Beschrijf je bevindingen, eventuele gebreken en benodigde reparaties."
                                    value={data.notes}
                                    onChange={(event) => setData('notes', event.target.value)}
                                    className={fieldClass + ' resize-y py-3'}
                                    aria-invalid={!!errors.notes}
                                    aria-describedby="notes-error"
                                />
                                <InputError id="notes-error" message={errors.notes} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="status">Apparaat is *</Label>
                                <select
                                    id="status"
                                    required
                                    value={data.status}
                                    onChange={(event) => setData('status', event.target.value)}
                                    className={fieldClass}
                                    aria-invalid={!!errors.status}
                                    aria-describedby="status-error"
                                >
                                    <option value="">Selecteer een status</option>
                                    <option value="goedgekeurd">Goedgekeurd</option>
                                    <option value="afgekeurd">Afgekeurd</option>
                                    <option value="onderhoud nodig">Onderhoud nodig (reparatie nodig)</option>
                                </select>
                                <InputError id="status-error" message={errors.status} />
                            </div>
                        </fieldset>
                        <Button type="submit" disabled={processing} className="h-12 w-full rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
                            {processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Keuring opslaan…' : 'Beëindig keuring'}
                        </Button>
                    </form>
                </div>
            </main>
        </div>
    );
}
