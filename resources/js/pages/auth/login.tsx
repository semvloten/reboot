import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, Gamepad2, Laptop, LoaderCircle, Power, ShieldCheck, Smartphone } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <main lang="nl" className="min-h-svh bg-[#F3F4F6] text-[#111827] lg:grid lg:grid-cols-2">
            <Head title="Inloggen | Reboot" />

            <section className="relative flex flex-col overflow-hidden bg-[#111827] px-6 py-8 text-white sm:px-12 lg:min-h-svh lg:px-16 lg:py-12">
                <Link
                    href={route('home')}
                    className="relative z-10 flex w-fit items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-8 focus-visible:outline-emerald-400"
                    aria-label="Reboot - naar de homepage"
                >
                    <span className="flex size-10 items-center justify-center rounded-xl bg-[#10B981] text-[#111827]">
                        <Power className="size-6" aria-hidden="true" />
                    </span>
                    <span className="text-2xl font-bold tracking-tight">
                        reboot<span className="text-[#10B981]">.</span>
                    </span>
                </Link>

                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/4 -right-44 size-[32rem] rounded-full border border-white/5 lg:size-[40rem]"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/3 -right-28 size-96 rounded-full border border-emerald-400/10"
                />

                <div className="relative z-10 mx-auto w-full max-w-lg py-10 lg:my-auto lg:py-16">
                    <p className="mb-5 flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-[#60A5FA] uppercase">
                        <span className="size-1.5 rounded-full bg-[#60A5FA]" aria-hidden="true" />
                        Refurbished. Ready. Reboot.
                    </p>
                    <h2 className="text-4xl leading-tight font-semibold tracking-tight sm:text-5xl xl:text-6xl">
                        Geef technologie
                        <br />
                        een <span className="text-[#10B981]">tweede leven.</span>
                    </h2>
                    <p className="mt-6 max-w-sm text-base leading-7 text-slate-300">
                        Jouw volgende laptop, console of telefoon begint met een goede keuring.
                    </p>

                    <div className="mt-10 hidden rounded-2xl border border-white/10 bg-white/5 p-6 sm:block">
                        <div className="mb-6 flex items-center gap-3" aria-hidden="true">
                            <span className="flex size-12 items-center justify-center rounded-xl bg-white/5 text-slate-300">
                                <Laptop className="size-6" />
                            </span>
                            <span className="flex size-12 items-center justify-center rounded-xl bg-white/5 text-slate-300">
                                <Gamepad2 className="size-6" />
                            </span>
                            <span className="flex size-12 items-center justify-center rounded-xl bg-white/5 text-slate-300">
                                <Smartphone className="size-6" />
                            </span>
                        </div>
                        <h3 className="font-semibold">Een nieuwe start. Technisch gecontroleerd.</h3>
                        <p className="mt-2 text-sm leading-6 text-slate-400">Van aanmelding tot keuring: je weet waar je apparaat aan toe is.</p>
                        <div className="mt-5 flex items-center gap-2 text-sm text-emerald-300">
                            <ShieldCheck className="size-5 shrink-0" aria-hidden="true" />
                            Alleen goedgekeurde apparaten in de winkel
                        </div>
                    </div>
                </div>

                <p className="relative hidden text-xs text-slate-400 lg:block">Reboot &middot; Een initiatief van Circuit Renew</p>
            </section>

            <section aria-labelledby="login-heading" className="flex flex-col px-6 py-8 sm:px-12 lg:px-16 lg:py-12">
                <Link
                    href={route('home')}
                    className="flex w-fit items-center gap-2 rounded text-sm text-slate-500 transition-colors hover:text-[#111827] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Terug naar home
                </Link>

                <div className="mx-auto w-full max-w-md py-12 lg:my-auto lg:py-16">
                    <div className="mb-8">
                        <p className="mb-3 text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Jouw Reboot-account</p>
                        <h1 id="login-heading" className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            Welkom terug.
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-slate-500">Log in en ga verder met jouw apparaten en keuringen.</p>
                    </div>

                    {status && (
                        <div
                            role="status"
                            className="mb-6 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
                        >
                            <Check className="size-5 shrink-0" aria-hidden="true" />
                            {status}
                        </div>
                    )}

                    <form className="space-y-6" onSubmit={submit}>
                        <div className="space-y-2">
                            <Label htmlFor="email" className="text-sm font-medium">
                                E-mailadres
                            </Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="jij@voorbeeld.nl"
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            />
                            <InputError id="email-error" message={errors.email} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password" className="text-sm font-medium">
                                Wachtwoord
                            </Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Vul je wachtwoord in"
                                aria-invalid={!!errors.password}
                                aria-describedby={errors.password ? 'password-error' : undefined}
                                className="h-12 rounded-xl border-slate-200 bg-white px-4 text-[#111827] placeholder:text-slate-400 focus-visible:ring-emerald-600"
                            />
                            <InputError id="password-error" message={errors.password} />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-2.5">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) => setData('remember', checked === true)}
                                    className="border-slate-300 data-[state=checked]:border-emerald-700 data-[state=checked]:bg-emerald-700 data-[state=checked]:text-white"
                                />
                                <Label htmlFor="remember" className="cursor-pointer text-sm font-normal text-slate-600">
                                    Onthoud mij
                                </Label>
                            </div>
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="text-sm font-medium text-emerald-700 decoration-emerald-700/30">
                                    Wachtwoord vergeten?
                                </TextLink>
                            )}
                        </div>

                        <Button
                            type="submit"
                            className="h-12 w-full rounded-xl bg-[#10B981] text-base font-semibold text-[#111827] hover:bg-emerald-400 focus-visible:ring-emerald-600"
                            disabled={processing}
                        >
                            {processing ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : null}
                            {processing ? 'Bezig met inloggen...' : 'Inloggen'}
                            {!processing && <ArrowRight className="size-4" aria-hidden="true" />}
                        </Button>
                    </form>

                    <p className="mt-8 border-t border-slate-200 pt-7 text-center text-sm text-slate-500">
                        Nieuw bij Reboot?{' '}
                        <TextLink href={route('register')} className="font-semibold text-emerald-700 decoration-emerald-700/30">
                            Maak een account aan
                        </TextLink>
                    </p>
                </div>

                <p className="text-center text-xs text-slate-500">Een tweede leven voor technologie. Een slimme keuze voor jou.</p>
            </section>
        </main>
    );
}
