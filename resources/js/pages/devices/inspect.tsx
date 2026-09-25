import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

export default function InspectDevice({ device }: { device: { id: number; brand: string; model: string } }) {
    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Apparaat keuren | Reboot" />
            <RebootNavbar showDashboard />
            <main className="mx-auto max-w-5xl p-4 sm:p-8">
                <div className="rounded-lg border bg-white p-6">
                    <h1 className="text-2xl font-bold">Apparaat keuren</h1>
                    <p className="mt-3 font-medium">
                        {device.brand} {device.model} · #{device.id}
                    </p>
                    <p className="mt-3 text-slate-600">De keuringspagina is nog in ontwikkeling. Je kunt hier binnenkort de keuring vastleggen.</p>
                    <Button asChild className="mt-6 bg-[#10B981] text-[#111827] hover:bg-[#10B981]/80">
                        <Link href={route('inspector.devices.index')}>Terug naar overzicht</Link>
                    </Button>
                </div>
            </main>
        </div>
    );
}
