import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/reboot-auth-layout';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_inspector: boolean;
};

interface RegisterProps {
    canCreateInspector: boolean;
    status?: string;
}

export default function Register({ canCreateInspector, status }: RegisterProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        is_inspector: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onSuccess: () => {
                if (canCreateInspector) {
                    reset();
                }
            },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={canCreateInspector ? 'Maak een account aan.' : 'Maak je account aan.'}
            description={
                canCreateInspector
                    ? 'Maak een klantaccount of een account voor een keurmeester aan.'
                    : 'Meld je aan bij Reboot en geef jouw apparaten een tweede leven.'
            }
        >
            <Head title="Registreren | Reboot" />
            {status && (
                <div role="status" className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {status}
                </div>
            )}
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label className="text-sm font-medium" htmlFor="name">
                            Naam
                        </Label>
                        <Input
                            className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            id="name"
                            name="name"
                            aria-invalid={!!errors.name}
                            aria-describedby={errors.name ? 'name-error' : undefined}
                            type="text"
                            required
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={processing}
                            placeholder="Je volledige naam"
                        />
                        <InputError id="name-error" message={errors.name} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label className="text-sm font-medium" htmlFor="email">
                            E-mailadres
                        </Label>
                        <Input
                            className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            id="email"
                            name="email"
                            aria-invalid={!!errors.email}
                            aria-describedby={errors.email ? 'email-error' : undefined}
                            type="email"
                            required
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                            placeholder="jij@example.test"
                        />
                        <InputError id="email-error" message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label className="text-sm font-medium" htmlFor="password">
                            Wachtwoord
                        </Label>
                        <Input
                            className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            id="password"
                            name="password"
                            aria-invalid={!!errors.password}
                            aria-describedby={errors.password ? 'password-error' : undefined}
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            placeholder="Kies een wachtwoord"
                        />
                        <InputError id="password-error" message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label className="text-sm font-medium" htmlFor="password_confirmation">
                            Bevestig wachtwoord
                        </Label>
                        <Input
                            className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            id="password_confirmation"
                            name="password_confirmation"
                            aria-invalid={!!errors.password_confirmation}
                            aria-describedby={errors.password_confirmation ? 'password_confirmation-error' : undefined}
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            placeholder="Herhaal je wachtwoord"
                        />
                        <InputError id="password_confirmation-error" message={errors.password_confirmation} />
                    </div>

                    {canCreateInspector && (
                        <div className="grid gap-2">
                            <div className="flex items-center gap-2.5">
                                <Checkbox
                                    id="is_inspector"
                                    name="is_inspector"
                                    checked={data.is_inspector}
                                    onCheckedChange={(checked) => setData('is_inspector', checked === true)}
                                    disabled={processing}
                                    aria-invalid={!!errors.is_inspector}
                                    aria-describedby={errors.is_inspector ? 'is-inspector-error' : undefined}
                                    className="border-slate-300 data-[state=checked]:border-emerald-700 data-[state=checked]:bg-emerald-700 data-[state=checked]:text-white"
                                />
                                <Label htmlFor="is_inspector" className="cursor-pointer text-sm font-normal text-slate-600">
                                    Maak dit account een keurmeester
                                </Label>
                            </div>
                            <InputError id="is-inspector-error" message={errors.is_inspector} />
                        </div>
                    )}

                    <Button
                        type="submit"
                        className="h-12 w-full rounded-xl bg-[#10B981] text-base font-semibold text-[#111827] hover:bg-emerald-400 focus-visible:ring-emerald-600"
                        disabled={processing}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        {processing ? 'Account aanmaken...' : 'Account aanmaken'}
                    </Button>
                </div>

                <div className="mt-8 border-t border-slate-200 pt-7 text-center text-sm text-slate-500">
                    {canCreateInspector ? (
                        <TextLink className="font-semibold text-emerald-700 decoration-emerald-700/30" href={route('dashboard')}>
                            Terug naar dashboard
                        </TextLink>
                    ) : (
                        <>
                            Heb je al een account?{' '}
                            <TextLink className="font-semibold text-emerald-700 decoration-emerald-700/30" href={route('login')}>
                                Inloggen
                            </TextLink>
                        </>
                    )}
                </div>
            </form>
        </AuthLayout>
    );
}
