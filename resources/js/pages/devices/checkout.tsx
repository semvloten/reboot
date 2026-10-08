import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CreditCard, LockKeyhole } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface CheckoutDevice {
    id: number;
    brand: string;
    model: string;
    asking_price: string;
}

export default function Checkout({ device }: { device: CheckoutDevice }) {
    const { post, processing } = useForm({});
    // Bevestigt een reservering; deze demo schrijft geen geld af.
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('shop.reserve', device.id));
    };

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Demo betaling | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-xl p-4 sm:p-8">
                <Link href={route('shop.show', device.id)} className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-emerald-700">
                    <ArrowLeft aria-hidden="true" className="size-4" /> Terug naar het product
                </Link>
                <form onSubmit={submit} className="mt-6 rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
                    <div className="flex items-center justify-between gap-4">
                        <CreditCard aria-hidden="true" className="size-8 text-emerald-600" />
                        <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">DEMO BETALING</span>
                    </div>
                    <h1 className="mt-6 text-2xl font-bold">Reservering afronden</h1>
                    <p className="mt-2 text-sm leading-6 text-slate-600">
                        Dit is een demo-betaling. Er wordt geen geld afgeschreven en je hoeft geen betaalgegevens in te vullen.
                    </p>
                    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                        <span className="min-w-0 font-medium break-words">
                            {device.brand} {device.model}
                        </span>
                        <span className="text-xl font-bold">
                            {Number(device.asking_price).toLocaleString('nl-NL', { style: 'currency', currency: 'EUR' })}
                        </span>
                    </div>
                    <div aria-label="Voorbeeld betaalpas" className="mt-6 rounded-xl bg-slate-900 p-6 text-white">
                        <div className="flex justify-between text-sm">
                            <span>Reboot Demo Bank</span>
                            <CreditCard aria-hidden="true" className="size-5 text-emerald-300" />
                        </div>
                        <p className="mt-8 font-mono text-lg tracking-widest">**** **** **** 4242</p>
                        <p className="mt-4 text-xs tracking-widest text-slate-300">VOORBEELDPAS</p>
                    </div>
                    <p className="mt-6 flex items-start gap-2 text-sm text-slate-600">
                        <LockKeyhole aria-hidden="true" className="mt-0.5 size-4 shrink-0" />
                        Met de knop hieronder bevestig je de reservering voor jouw account.
                    </p>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="mt-6 h-12 w-full bg-[#10B981] font-semibold text-[#111827] hover:bg-[#10B981]/80"
                    >
                        {processing ? 'Reserveren...' : 'Reservering bevestigen'}
                    </Button>
                </form>
            </main>
        </div>
    );
}
