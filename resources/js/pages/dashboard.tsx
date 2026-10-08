/**
 * Onderdeel: Winkelgrid met filters, productspecificaties en lege-resultatenmelding; server levert uitsluitend eerder goedgekeurde producten.
 * Eisen: FE-11, RV-05, RV-06, RV-07.
 * Ontwerp: T-18 (winkelweergave).
 * Bouw: T-19 (winkelweergave).
 * Geplande controle: T-20.
 * Toelichting: Gereserveerde producten blijven zichtbaar met status gereserveerd en zijn niet opnieuw te reserveren (FE-12).
 */

import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link, useRemember } from '@inertiajs/react';
import { Search } from 'lucide-react';

interface ShopDevice {
    id: number;
    type: string;
    brand: string;
    model: string;
    condition: string;
    accessories: string | null;
    asking_price: string;
    status: 'goedgekeurd' | 'gereserveerd';
    photo_url: string | null;
}

function typeLabel(type: string) {
    return type === 'laptops' ? 'Laptop' : type.charAt(0).toUpperCase() + type.slice(1);
}

// Dit is de winkelpagina; de server levert goedgekeurde en gereserveerde producten.
export default function Shop({ devices, status }: { devices: ShopDevice[]; status?: string }) {
    // Bewaart de zoek- en filterkeuzes wanneer je vanuit een product terugkomt.
    const [filters, setFilters] = useRemember({ search: '', type: '', condition: '', sort: 'price-asc' }, 'Shop.filters');
    const types = [...new Set(devices.map((device) => device.type))].sort();
    const conditions = [...new Set(devices.map((device) => device.condition))].sort();
    const search = filters.search.trim().toLocaleLowerCase('nl-NL');
    // Filtert de ontvangen producten en sorteert de prijzen als getallen.
    const filteredDevices = devices
        .filter(
            (device) =>
                (!filters.type || device.type === filters.type) &&
                (!filters.condition || device.condition === filters.condition) &&
                (device.brand + ' ' + device.model).toLocaleLowerCase('nl-NL').includes(search),
        )
        .sort((a, b) =>
            filters.sort === 'price-desc' ? Number(b.asking_price) - Number(a.asking_price) : Number(a.asking_price) - Number(b.asking_price),
        );
    const fieldClass =
        'h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600';

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Winkel | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-6xl p-4 sm:p-8">
                <h1 className="text-3xl font-bold">Winkel</h1>
                <p className="mt-2 text-sm text-slate-600">Gecontroleerde apparaten, klaar voor een tweede leven.</p>
                {status && (
                    <div role="status" className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                        {status}
                    </div>
                )}
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr]">
                    <div>
                        <label htmlFor="shop-search" className="mb-2 block text-sm font-medium">
                            Zoek apparaat
                        </label>
                        <div className="relative">
                            <Search aria-hidden="true" className="pointer-events-none absolute top-4 left-3 size-4 text-slate-500" />
                            <input
                                id="shop-search"
                                type="search"
                                className={fieldClass + ' pl-10'}
                                placeholder="Zoek op merk of model"
                                value={filters.search}
                                onChange={(event) => setFilters({ ...filters, search: event.target.value })}
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="shop-type" className="mb-2 block text-sm font-medium">
                            Type apparaat
                        </label>
                        <select
                            id="shop-type"
                            className={fieldClass}
                            value={filters.type}
                            onChange={(event) => setFilters({ ...filters, type: event.target.value })}
                        >
                            <option value="">Alle typen</option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {typeLabel(type)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="shop-condition" className="mb-2 block text-sm font-medium">
                            Conditie
                        </label>
                        <select
                            id="shop-condition"
                            className={fieldClass}
                            value={filters.condition}
                            onChange={(event) => setFilters({ ...filters, condition: event.target.value })}
                        >
                            <option value="">Alle condities</option>
                            {conditions.map((condition) => (
                                <option key={condition} value={condition}>
                                    {condition.charAt(0).toUpperCase() + condition.slice(1)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="shop-sort" className="mb-2 block text-sm font-medium">
                            Sorteren
                        </label>
                        <select
                            id="shop-sort"
                            className={fieldClass}
                            value={filters.sort}
                            onChange={(event) => setFilters({ ...filters, sort: event.target.value })}
                        >
                            <option value="price-asc">Prijs laag-hoog</option>
                            <option value="price-desc">Prijs hoog-laag</option>
                        </select>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p role="status" className="text-sm text-slate-600">
                        {filteredDevices.length} van {devices.length} apparaten
                    </p>
                    {(filters.search || filters.type || filters.condition) && (
                        <Button
                            className="bg-[#60A5FA] text-white hover:bg-[#60A5FA]/80 hover:text-white"
                            onClick={() => setFilters({ ...filters, search: '', type: '', condition: '' })}
                        >
                            Filters wissen
                        </Button>
                    )}
                </div>
                {filteredDevices.length === 0 ? (
                    <div className="mt-6 rounded-lg border bg-white p-8 text-center text-slate-600">
                        {devices.length === 0
                            ? 'Er zijn nog geen goedgekeurde apparaten beschikbaar.'
                            : 'Geen apparaten gevonden. Pas je zoekopdracht of filters aan.'}
                    </div>
                ) : (
                    <div className="mt-6 grid gap-6 md:grid-cols-2">
                        {filteredDevices.map((device) => (
                            <article key={device.id} className="flex min-h-72 min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-6">
                                <div className="flex flex-col gap-5 sm:flex-row">
                                    {device.photo_url ? (
                                        <img
                                            src={device.photo_url}
                                            alt={device.brand + ' ' + device.model}
                                            loading="lazy"
                                            className="h-40 w-full shrink-0 rounded-lg bg-slate-50 object-contain sm:w-36"
                                        />
                                    ) : (
                                        <div className="flex h-40 w-full shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500 sm:w-36">
                                            Geen foto
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <h2 className="text-xl font-semibold break-words">
                                            {device.brand} {device.model}
                                        </h2>
                                        <dl className="mt-4 space-y-2 text-sm">
                                            <div className="flex gap-2">
                                                <dt className="text-slate-500">Type:</dt>
                                                <dd className="break-words">{typeLabel(device.type)}</dd>
                                            </div>
                                            <div className="flex gap-2">
                                                <dt className="text-slate-500">Conditie:</dt>
                                                <dd className="break-words">{device.condition}</dd>
                                            </div>
                                            <div className="flex gap-2">
                                                <dt className="shrink-0 text-slate-500">Accessoires:</dt>
                                                <dd className="min-w-0 break-words whitespace-pre-wrap">{device.accessories || 'Geen'}</dd>
                                            </div>
                                            <div className="pt-2">
                                                <dt className="sr-only">Prijs</dt>
                                                <dd className="text-xl font-bold">
                                                    {Number(device.asking_price).toLocaleString('nl-NL', { style: 'currency', currency: 'EUR' })}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                                <div className="mt-auto flex flex-col items-end gap-3 pt-6">
                                    {device.status === 'gereserveerd' ? (
                                        <>
                                            <span className="rounded-md bg-slate-200 px-3 py-1 text-sm font-medium text-slate-700">Gereserveerd</span>
                                            <Button
                                                disabled
                                                variant="outline"
                                                className="border-slate-200 bg-slate-200 text-slate-700 hover:bg-slate-200 disabled:opacity-100"
                                            >
                                                Niet beschikbaar
                                            </Button>
                                        </>
                                    ) : (
                                        <Button asChild className="bg-[#10B981] font-semibold text-[#111827] hover:bg-[#10B981]/80">
                                            <Link
                                                href={route('shop.show', device.id)}
                                                aria-label={'Bekijk product: ' + device.brand + ' ' + device.model}
                                            >
                                                Bekijk product
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
