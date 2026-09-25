import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

export default function Product({ device }: { device: { id: number; brand: string; model: string } }) {
    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Bekijk product | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-6xl p-4 sm:p-8">
                <div className="rounded-xl border border-slate-200 bg-white p-6">
                    <h1 className="text-2xl font-bold">
                        {device.brand} {device.model}
                    </h1>
                    <p className="mt-3 text-slate-600">
                        De productpagina is nog in ontwikkeling. Binnenkort vind je hier meer informatie over dit apparaat.
                    </p>
                    <Button asChild className="mt-6 bg-[#10B981] text-[#111827] hover:bg-[#10B981]/80">
                        <Link href={route('shop.index')}>Terug naar de winkel</Link>
                    </Button>
                </div>
            </main>
        </div>
    );
}
