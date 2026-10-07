import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/reboot-auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    // Vraagt per e-mail een resetlink aan voor het opgegeven account.
    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout title="Wachtwoord vergeten?" description="Vul het e-mailadres van je account in om een resetlink aan te vragen.">
            <Head title="Wachtwoord vergeten | Reboot" />

            {status && (
                <div role="status" className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <form onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label className="text-sm font-medium" htmlFor="email">
                            E-mailadres
                        </Label>
                        <Input
                            className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            id="email"
                            aria-invalid={!!errors.email}
                            aria-describedby={errors.email ? 'email-error' : undefined}
                            type="email"
                            name="email"
                            autoComplete="email"
                            required
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="jij@example.test"
                        />

                        <InputError id="email-error" message={errors.email} />
                    </div>

                    <div className="my-6 flex items-center justify-start">
                        <Button
                            type="submit"
                            className="h-12 w-full rounded-xl bg-[#10B981] text-base font-semibold text-[#111827] hover:bg-emerald-400 focus-visible:ring-emerald-600"
                            disabled={processing}
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {processing ? 'Aanvragen...' : 'Resetlink aanvragen'}
                        </Button>
                    </div>
                </form>

                <div className="mt-8 space-x-1 border-t border-slate-200 pt-7 text-center text-sm text-slate-500">
                    <span>Weet je je wachtwoord weer?</span>
                    <TextLink className="font-semibold text-emerald-700 decoration-emerald-700/30" href={route('login')}>
                        Terug naar inloggen
                    </TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
