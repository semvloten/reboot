import RebootNavbar from '@/components/reboot-navbar';
import { Head, Link } from '@inertiajs/react';

interface ReservedDevice {
    id: number;
    brand: string;
    model: string;
    asking_price: string;
    photo_url: string | null;
}

export default function Reservations({ reservedDevices }: { reservedDevices: ReservedDevice[] }) {
    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Mijn reserveringen | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-5xl p-4 sm:p-8">
                <section aria-labelledby="reservations-title">
                    <h1 id="reservations-title" className="text-2xl font-bold">Mijn reserveringen</h1>
                    {reservedDevices.length === 0 ? (
                        <p className="mt-4 text-slate-600">Je hebt nog geen apparaten gereserveerd.</p>
                    ) : (
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            {reservedDevices.map((device) => (
                                <article key={device.id} className="rounded-xl border border-slate-200 bg-white p-5">
                                    {device.photo_url && (
                                        <img
                                            src={device.photo_url}
                                            alt={device.brand + ' ' + device.model}
                                            loading="lazy"
                                            className="mb-4 h-40 w-full rounded-lg object-contain"
                                        />
                                    )}
                                    <h2 className="text-lg font-semibold break-words">{device.brand} {device.model}</h2>
                                    <p className="mt-2 font-medium">
                                        {Number(device.asking_price).toLocaleString('nl-NL', { style: 'currency', currency: 'EUR' })}
                                    </p>
                                    <p className="mt-2 text-sm text-emerald-700">Voor jou gereserveerd</p>
                                    <Link href={route('shop.show', device.id)} className="mt-4 inline-block text-emerald-700 underline">
                                        Product bekijken
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </main>
        </div>
    );
}
