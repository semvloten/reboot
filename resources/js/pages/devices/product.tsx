import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ImageIcon } from 'lucide-react';
import { useState } from 'react';

interface ProductDevice {
    id: number;
    type: string;
    brand: string;
    model: string;
    serial_number: string;
    condition: string;
    accessories: string | null;
    asking_price: string;
    status: 'goedgekeurd' | 'gereserveerd';
    photo_urls: string[];
}

export default function Product({ device, canReserve, status }: { device: ProductDevice; canReserve: boolean; status?: string }) {
    const [selectedPhoto, setSelectedPhoto] = useState(0);
    const title = device.brand + ' ' + device.model;
    const reserved = device.status === 'gereserveerd';

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title={title + ' | Reboot'} />
            <RebootNavbar />
            <main className="mx-auto max-w-6xl p-4 sm:p-8">
                <Link href={route('shop.index')} className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-emerald-700">
                    <ArrowLeft aria-hidden="true" className="size-4" /> Terug naar de winkel
                </Link>
                <h1 className="mt-5 text-3xl font-bold break-words">{title}</h1>
                {status && (
                    <p role="status" className="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        {status}
                    </p>
                )}
                <div className="mt-6 grid gap-6 lg:grid-cols-2">
                    <section aria-label="Productfoto's" className="min-w-0 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                        {device.photo_urls.length > 0 ? (
                            <>
                                <img
                                    src={device.photo_urls[selectedPhoto] ?? device.photo_urls[0]}
                                    alt={title + ' - foto ' + (selectedPhoto + 1)}
                                    className="aspect-square w-full rounded-lg bg-slate-50 object-contain"
                                />
                                <div className="mt-4 flex flex-wrap gap-3">
                                    {device.photo_urls.map((url, index) => (
                                        <button
                                            key={url}
                                            type="button"
                                            onClick={() => setSelectedPhoto(index)}
                                            aria-label={'Bekijk foto ' + (index + 1)}
                                            aria-pressed={selectedPhoto === index}
                                            className={
                                                'size-20 overflow-hidden rounded-lg border-2 p-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 ' +
                                                (selectedPhoto === index ? 'border-emerald-500' : 'border-slate-200')
                                            }
                                        >
                                            <img src={url} alt="" loading="lazy" className="h-full w-full object-contain" />
                                        </button>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="flex aspect-square flex-col items-center justify-center gap-3 rounded-lg bg-slate-50 text-slate-500">
                                <ImageIcon aria-hidden="true" className="size-12" />
                                <p>Geen foto's beschikbaar</p>
                            </div>
                        )}
                    </section>
                    <section aria-label="Productgegevens" className="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                        <p className="text-sm text-slate-500">Prijs</p>
                        <p className="mt-1 text-3xl font-bold">
                            {Number(device.asking_price).toLocaleString('nl-NL', { style: 'currency', currency: 'EUR' })}
                        </p>
                        <p className="mt-4 text-sm">
                            Status:{' '}
                            <span
                                className={
                                    'ml-2 inline-block rounded-md px-3 py-1 font-medium ' +
                                    (reserved ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800')
                                }
                            >
                                {reserved ? 'Gereserveerd' : 'Beschikbaar'}
                            </span>
                        </p>
                        <div className="my-6 rounded-lg border border-slate-200 p-4">
                            <h2 className="text-lg font-semibold">Productspecificaties</h2>
                            <dl className="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-3 text-sm">
                                {[
                                    ['Type', device.type === 'laptops' ? 'Laptop' : device.type],
                                    ['Merk', device.brand],
                                    ['Model', device.model],
                                    ['Conditie', device.condition],
                                    ['Accessoires', device.accessories || 'Geen'],
                                    ['Serienummer', device.serial_number],
                                    ['Apparaat-ID', 'APP-' + device.id],
                                ].map(([label, value]) => (
                                    <div key={label} className="contents">
                                        <dt className="text-slate-500">{label}</dt>
                                        <dd className="break-words whitespace-pre-wrap">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                        <div className="mt-auto">
                            {canReserve ? (
                                <Button asChild className="h-12 w-full bg-[#10B981] font-semibold text-[#111827] hover:bg-[#10B981]/80">
                                    <Link href={route('shop.checkout', device.id)}>Reserveer product</Link>
                                </Button>
                            ) : (
                                <Button disabled className="h-12 w-full bg-slate-200 text-slate-700 disabled:opacity-100">
                                    {reserved ? 'Gereserveerd' : 'Alleen klanten kunnen reserveren'}
                                </Button>
                            )}
                        </div>
                    </section>
                </div>
                <section aria-labelledby="inspection-heading" className="mt-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                    <h2 id="inspection-heading" className="text-lg font-semibold">
                        Keuringsinformatie
                    </h2>
                    <p className="mt-3 text-sm leading-6 text-slate-600">
                        Hier verschijnt binnenkort informatie over de batterij, het scherm, de aansluitingen en de fabrieksreset. Ook de opmerkingen
                        van de keurmeester worden hier getoond.
                    </p>
                    <p className="mt-2 text-sm text-slate-500">
                        Dit is tijdelijke voorbeeldtekst; de echte keuringsresultaten worden later toegevoegd.
                    </p>
                </section>
            </main>
        </div>
    );
}
