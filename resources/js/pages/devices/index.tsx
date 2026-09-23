import RebootNavbar from '@/components/reboot-navbar';
import { Head, Link } from '@inertiajs/react';

interface Device {
    id: number;
    type: string;
    brand: string;
    model: string;
    serial_number: string;
    condition: string;
    accessories: string | null;
    asking_price: string;
    photo_url: string | null;
}

export default function Devices({ devices }: { devices: Device[] }) {
    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Mijn apparaten | Reboot" />
            <RebootNavbar showDashboard />
            <main className="mx-auto max-w-5xl p-4 sm:p-8">
                <h1 className="mb-4 text-2xl font-bold">Mijn apparaten</h1>
                <Link href={route('devices.create')} className="text-emerald-700 underline">
                    Apparaat aanmelden
                </Link>
                {devices.length === 0 ? (
                    <p className="mt-6">Je hebt nog geen apparaten aangemeld.</p>
                ) : (
                    <div className="mt-6 overflow-x-auto rounded-lg border bg-white">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-100">
                                <tr>
                                    {['Foto', 'Type', 'Merk', 'Model', 'Serienummer', 'Conditie', 'Accessoires', 'Vraagprijs'].map((label) => (
                                        <th key={label} scope="col" className="p-3">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {devices.map((device) => (
                                    <tr key={device.id} className="border-t">
                                        <td className="p-3">
                                            {device.photo_url ? (
                                                <img
                                                    src={device.photo_url}
                                                    alt={device.brand + ' ' + device.model}
                                                    loading="lazy"
                                                    className="size-20 min-w-20 rounded object-cover"
                                                />
                                            ) : (
                                                <span className="text-slate-500">Geen foto</span>
                                            )}
                                        </td>
                                        <td className="p-3 capitalize">{device.type}</td>
                                        <td className="p-3">{device.brand}</td>
                                        <td className="p-3">{device.model}</td>
                                        <td className="p-3">{device.serial_number}</td>
                                        <td className="p-3 capitalize">{device.condition}</td>
                                        <td className="p-3">{device.accessories || 'Geen'}</td>
                                        <td className="p-3 whitespace-nowrap">
                                            {Number(device.asking_price).toLocaleString('nl-NL', { style: 'currency', currency: 'EUR' })}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </main>
        </div>
    );
}
