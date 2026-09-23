import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReactNode } from 'react';

interface RebootAuthLayoutProps {
    children: ReactNode;
    title: string;
    description: string;
    showBackLink?: boolean;
}

export default function RebootAuthLayout({ children, title, description, showBackLink = true }: RebootAuthLayoutProps) {
    return (
        <main lang="nl" className="flex min-h-svh flex-col bg-[#F3F4F6] px-6 py-8 text-[#111827] sm:px-12 lg:py-12">
            {showBackLink && (
                <Link
                    href={route('dashboard')}
                    className="flex w-fit items-center gap-2 rounded text-sm text-slate-500 transition-colors hover:text-[#111827] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Terug naar dashboard
                </Link>
            )}
            <section aria-labelledby="auth-heading" className="mx-auto my-auto w-full max-w-md py-12">
                <div className="mb-8">
                    <p className="mb-3 text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Jouw Reboot-account</p>
                    <h1 id="auth-heading" className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {title}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-slate-500">{description}</p>
                </div>
                {children}
            </section>
            <p className="text-center text-xs text-slate-500">Een tweede leven voor technologie. Een slimme keuze voor jou.</p>
        </main>
    );
}
