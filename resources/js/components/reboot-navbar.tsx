import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Power } from 'lucide-react';

interface RebootNavbarProps {
    showLogin?: boolean;
    showRegister?: boolean;
    showLogout?: boolean;
    showDashboard?: boolean;
}

export default function RebootNavbar({ showLogin = true, showRegister = true, showLogout = true, showDashboard = false }: RebootNavbarProps) {
    const { auth } = usePage<SharedData>().props;
    const canShowRegister = showRegister && auth.user?.role === 'inspector';
    const canShowLogout = showLogout && !!auth.user;

    return (
        <nav
            aria-label="Hoofdnavigatie"
            className="flex min-h-20 w-full flex-wrap items-center gap-3 border-b border-[#111827]/10 bg-[#F3F4F6] px-4 py-3 text-[#111827] sm:px-6"
        >
            <Link
                href={route('home')}
                aria-label="Reboot home"
                className="flex size-12 shrink-0 items-center justify-center rounded-xl border border-[#111827]/10 bg-white transition-colors hover:bg-[#60A5FA]/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#60A5FA]"
            >
                <Power className="size-6 text-[#10B981]" aria-hidden="true" />
            </Link>
            {showLogin && !auth.user && (
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                >
                    <Link href={route('login')}>Login</Link>
                </Button>
            )}
            {showDashboard && auth.user && (
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                >
                    <Link href={route('dashboard')}>Dashboard</Link>
                </Button>
            )}
            {auth.user?.role === 'inspector' && (
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                >
                    <Link href={route('inspector.devices.index')}>Apparaten keuren</Link>
                </Button>
            )}
            {auth.user?.role === 'customer' && (
                <>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                    >
                        <Link href={route('devices.index')}>Mijn apparaten</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                    >
                        <Link href={route('devices.create')}>Apparaat toevoegen</Link>
                    </Button>
                </>
            )}
            {(canShowRegister || canShowLogout) && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="ml-auto h-12 rounded-xl border-transparent bg-[#10B981] px-4 font-semibold text-[#111827] hover:bg-[#10B981]/80 hover:text-[#111827] focus-visible:ring-[#10B981]"
                        >
                            Meer opties
                            <ChevronDown className="size-4" aria-hidden="true" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="rounded-xl border-[#111827]/10 bg-[#F3F4F6] p-1.5 text-[#111827]">
                        {canShowRegister && (
                            <DropdownMenuItem asChild className="min-h-11 rounded-lg px-3 focus:bg-[#10B981]/20 focus:text-[#111827]">
                                <Link href={route('register')}>Regristreer nieuw account</Link>
                            </DropdownMenuItem>
                        )}
                        {canShowLogout && (
                            <DropdownMenuItem asChild className="min-h-11 rounded-lg px-3 focus:bg-[#10B981]/20 focus:text-[#111827]">
                                <Link href={route('logout')} method="post" as="button" className="w-full">
                                    Uitloggen
                                </Link>
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </nav>
    );
}
