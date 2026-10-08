import InputError from '@/components/input-error';
import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm, useRemember } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEventHandler } from 'react';

// Geeft elke keuringsstatus een leesbare naam en een herkenbare kleur.
const statuses = {
    'in behandeling': { label: 'In behandeling', color: 'bg-blue-500' },
    'onderhoud nodig': { label: 'Onderhoud nodig', color: 'bg-orange-500' },
    goedgekeurd: { label: 'Goedgekeurd', color: 'bg-green-500' },
    afgekeurd: { label: 'Afgekeurd', color: 'bg-red-500' },
};

interface Device {
    id: number;
    priority: number;
    assigned_to_user_id: number | null;
    assigned_inspector_name: string | null;
    type: string;
    brand: string;
    model: string;
    serial_number: string;
    condition: string;
    status: keyof typeof statuses;
    photo_url: string | null;
    created_at: string | null;
}

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleString('nl-NL', { dateStyle: 'short', timeStyle: 'short' }) : 'Onbekend';
}

type Inspector = { id: number; name: string };

function TriageForm({ device, inspectors }: { device: Device; inspectors: Inspector[] }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        priority: String(device.priority),
        assigned_to_user_id: device.assigned_to_user_id == null ? '' : String(device.assigned_to_user_id),
    });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('inspector.devices.triage', device.id), { preserveScroll: true });
    };
    const fieldClass = 'mt-2 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm';
    return (
        <form onSubmit={submit} className="mt-5 space-y-3 border-t border-slate-100 pt-4">
            <fieldset disabled={processing} className="grid gap-3 sm:grid-cols-2">
                <legend className="sr-only">
                    Prioriteit en toewijzing voor {device.brand} {device.model}
                </legend>
                <div>
                    <label htmlFor={'priority-' + device.id} className="text-sm font-medium">
                        Prioriteit
                    </label>
                    <select
                        id={'priority-' + device.id}
                        className={fieldClass}
                        value={data.priority}
                        onChange={(event) => setData('priority', event.target.value)}
                        aria-invalid={!!errors.priority}
                        aria-describedby={'priority-error-' + device.id}
                    >
                        <option value="2">Hoog</option>
                        <option value="1">Normaal</option>
                        <option value="0">Laag</option>
                    </select>
                    <InputError id={'priority-error-' + device.id} message={errors.priority} />
                </div>
                <div>
                    <label htmlFor={'assigned-' + device.id} className="text-sm font-medium">
                        Toewijzen aan
                    </label>
                    <select
                        id={'assigned-' + device.id}
                        className={fieldClass}
                        value={data.assigned_to_user_id}
                        onChange={(event) => setData('assigned_to_user_id', event.target.value)}
                        aria-invalid={!!errors.assigned_to_user_id}
                        aria-describedby={'assigned-error-' + device.id}
                    >
                        <option value="">Niet toegewezen</option>
                        {inspectors.map((inspector) => (
                            <option key={inspector.id} value={inspector.id}>
                                {inspector.name}
                            </option>
                        ))}
                    </select>
                    <InputError id={'assigned-error-' + device.id} message={errors.assigned_to_user_id} />
                </div>
            </fieldset>
            <Button type="submit" disabled={processing} variant="outline">
                {processing ? 'Opslaan...' : 'Toewijzing opslaan'}
            </Button>
            {recentlySuccessful && (
                <p role="status" className="text-sm text-emerald-700">
                    Prioriteit en toewijzing opgeslagen.
                </p>
            )}
        </form>
    );
}

export default function InspectorDevices({ devices, inspectors, status }: { devices: Device[]; inspectors: Inspector[]; status?: string }) {
    // Onthoudt de filters wanneer je een apparaat opent en teruggaat naar het overzicht.
    const [filters, setFilters] = useRemember({ search: '', status: '', type: '', assigned: '' }, 'InspectorDevices.filters');
    const types = [...new Set(devices.map((device) => device.type))].sort();
    const search = filters.search.trim().toLocaleLowerCase('nl-NL');
    // Combineert status, apparaattype en zoektekst zonder onderscheid tussen hoofdletters.
    const filteredDevices = devices.filter(
        (device) =>
            (!filters.status || device.status === filters.status) &&
            (!filters.type || device.type === filters.type) &&
            (!filters.assigned ||
                (filters.assigned === 'unassigned' ? device.assigned_to_user_id == null : String(device.assigned_to_user_id) === filters.assigned)) &&
            [device.brand, device.model, device.serial_number, device.type, device.condition, device.status]
                .join(' ')
                .toLocaleLowerCase('nl-NL')
                .includes(search),
    );
    const fieldClass =
        'h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600';

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Apparaten keuren | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-5xl p-4 sm:p-8">
                <h1 className="text-2xl font-bold">Apparaten keuren</h1>
                <p className="mt-2 text-sm text-slate-600">Bekijk alle aangemelde apparaten en hun keuringsstatus.</p>
                {status && (
                    <p role="status" className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        {status}
                    </p>
                )}
                <p className="mt-2 text-sm text-slate-600">
                    Hoge prioriteit staat bovenaan. Bij gelijke prioriteit staan de oudste aanmeldingen eerst.
                </p>
                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label htmlFor="device-status" className="mb-2 block text-sm font-medium">
                            Filter op status
                        </label>
                        <select
                            id="device-status"
                            className={fieldClass}
                            value={filters.status}
                            onChange={(event) => setFilters({ ...filters, status: event.target.value })}
                        >
                            <option value="">Alle statussen</option>
                            {Object.entries(statuses).map(([value, status]) => (
                                <option key={value} value={value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="device-search" className="mb-2 block text-sm font-medium">
                            Zoek apparaat
                        </label>
                        <div className="relative">
                            <Search aria-hidden="true" className="pointer-events-none absolute top-4 left-3 size-4 text-slate-500" />
                            <input
                                id="device-search"
                                type="search"
                                className={fieldClass + ' pl-10'}
                                placeholder="Zoek op merk, model of serienummer"
                                value={filters.search}
                                onChange={(event) => setFilters({ ...filters, search: event.target.value })}
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="device-type" className="mb-2 block text-sm font-medium">
                            Type apparaat
                        </label>
                        <select
                            id="device-type"
                            className={fieldClass}
                            value={filters.type}
                            onChange={(event) => setFilters({ ...filters, type: event.target.value })}
                        >
                            <option value="">Alle typen</option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {type === 'laptops' ? 'Laptop' : type.charAt(0).toUpperCase() + type.slice(1)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="device-assigned" className="mb-2 block text-sm font-medium">
                            Toegewezen keurmeester
                        </label>
                        <select
                            id="device-assigned"
                            className={fieldClass}
                            value={filters.assigned}
                            onChange={(event) => setFilters({ ...filters, assigned: event.target.value })}
                        >
                            <option value="">Alle keurmeesters</option>
                            <option value="unassigned">Niet toegewezen</option>
                            {inspectors.map((inspector) => (
                                <option key={inspector.id} value={inspector.id}>
                                    {inspector.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p role="status" className="text-sm text-slate-600">
                        {filteredDevices.length} van {devices.length} apparaten
                    </p>
                    {(filters.search || filters.status || filters.type || filters.assigned) && (
                        <Button
                            className="bg-[#60A5FA] text-white hover:bg-[#60A5FA]/80 hover:text-white"
                            onClick={() => setFilters({ search: '', status: '', type: '', assigned: '' })}
                        >
                            Filters wissen
                        </Button>
                    )}
                </div>
                {filteredDevices.length === 0 ? (
                    <div className="mt-6 rounded-lg border bg-white p-8 text-center text-slate-600">
                        {devices.length === 0
                            ? 'Er zijn nog geen apparaten aangemeld.'
                            : 'Geen apparaten gevonden. Pas je zoekopdracht of filters aan.'}
                    </div>
                ) : (
                    <div className="mt-6 grid gap-6 md:grid-cols-2">
                        {filteredDevices.map((device) => (
                            <article key={device.id} className="flex min-w-0 flex-col rounded-lg border border-slate-200 bg-white p-5">
                                <div className="flex items-start gap-4">
                                    {device.photo_url ? (
                                        <img
                                            src={device.photo_url}
                                            alt={device.brand + ' ' + device.model}
                                            loading="lazy"
                                            className="h-24 w-28 shrink-0 rounded bg-slate-100 object-contain"
                                        />
                                    ) : (
                                        <div className="flex h-24 w-28 shrink-0 items-center justify-center rounded bg-slate-100 text-sm text-slate-500">
                                            Geen foto
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <h2 className="text-lg font-semibold break-words">
                                            {device.brand} {device.model}
                                        </h2>
                                        <span
                                            className={
                                                'mt-2 inline-block rounded-md px-3 py-1 text-sm font-medium text-white ' +
                                                statuses[device.status].color
                                            }
                                        >
                                            {statuses[device.status].label}
                                        </span>
                                    </div>
                                </div>
                                <dl className="mt-5 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                                    {[
                                        ['Soort', device.type === 'laptops' ? 'Laptop' : device.type],
                                        ['Serienummer', device.serial_number],
                                        ['Conditie', device.condition],
                                        ['Aangemeld op', formatDate(device.created_at)],
                                        ['Prioriteit', device.priority === 2 ? 'Hoog' : device.priority === 0 ? 'Laag' : 'Normaal'],
                                        ['Keurmeester', device.assigned_inspector_name || 'Niet toegewezen'],
                                    ].map(([label, value]) => (
                                        <div key={label} className="contents">
                                            <dt className="text-slate-500">{label}</dt>
                                            <dd className="break-words whitespace-pre-wrap">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <TriageForm
                                    key={device.id + '-' + device.priority + '-' + device.assigned_to_user_id}
                                    device={device}
                                    inspectors={inspectors}
                                />
                                <div className="mt-auto flex justify-end pt-6">
                                    <Button asChild className="bg-[#10B981] font-semibold text-[#111827] hover:bg-[#10B981]/80">
                                        <Link
                                            href={route('inspector.devices.inspect', device.id)}
                                            aria-label={
                                                (device.status === 'in behandeling' ? 'Keur apparaat: ' : 'Keur opnieuw: ') +
                                                device.brand +
                                                ' ' +
                                                device.model
                                            }
                                        >
                                            {device.status === 'in behandeling' ? 'Keur apparaat' : 'Keur opnieuw'}
                                        </Link>
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
