import RebootNavbar from '@/components/reboot-navbar';
import { ReactNode } from 'react';

interface RebootAuthLayoutProps {
    children: ReactNode;
    title: string;
    description: string;
}

export default function RebootAuthLayout({ children, title, description }: RebootAuthLayoutProps) {
    return (
        <div lang="nl" className="flex min-h-svh flex-col bg-[#F3F4F6] text-[#111827]">
            <RebootNavbar />
            <main aria-labelledby="auth-heading" className="mx-auto my-auto w-full max-w-md px-6 py-12">
                <div className="mb-8">
                    <p className="mb-3 text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Jouw Reboot-account</p>
                    <h1 id="auth-heading" className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {title}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-slate-500">{description}</p>
                </div>
                {children}
            </main>
            <p className="px-6 pb-8 text-center text-xs text-slate-500">Een tweede leven voor technologie. Een slimme keuze voor jou.</p>
        </div>
    );
}
