import { Head, useForm } from '@inertiajs/react';
import { ArrowRight, Check, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import RebootAuthLayout from '@/layouts/auth/reboot-auth-layout';

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

    // Verstuurt de inloggegevens en wist het wachtwoord na iedere poging.
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <RebootAuthLayout title="Welkom terug." description="Log in en ga verder met jouw apparaten en keuringen.">
            <Head title="Inloggen | Reboot" />
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
        </RebootAuthLayout>
    );
}
