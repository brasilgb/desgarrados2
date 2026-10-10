import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, MapPin } from 'lucide-react';
import type { ReactNode } from 'react';
import { index as stories } from '@/routes/stories';
import { dashboard, home, login, register } from '@/routes';
import { index as administration } from '@/routes/admin';
import { index as states, myLand } from '@/routes/territory';
import type { User } from '@/types';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, can, hasStories } = usePage<{
        auth: { user: User | null };
        hasStories: boolean;
        can: { accessAdministration: boolean };
    }>().props;
    return (
        <div className="min-h-screen bg-[#f5f2e9] text-[#243e35] dark:bg-[#15251f] dark:text-[#eee9dc]">
            <a
                href="#conteudo"
                className="sr-only z-50 rounded bg-background p-4 text-foreground focus:not-sr-only focus:fixed"
            >
                Ir para o conteúdo
            </a>
            <header className="border-b border-current/15">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-6 py-6 lg:px-12">
                    <Link
                        href={home()}
                        className="flex items-center gap-3 rounded focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        <MapPin className="size-7" aria-hidden="true" />
                        <span className="text-2xl font-semibold tracking-tight">
                            desgarrados
                            <span className="text-[#9d4934] dark:text-[#e3a089]">
                                .
                            </span>
                        </span>
                    </Link>
                    <nav
                        aria-label="Navegação principal"
                        className="flex flex-wrap items-center gap-5 text-sm font-medium"
                    >
                        {hasStories && (
                            <Link
                                href={stories()}
                                className="rounded hover:underline"
                            >
                                Histórias
                            </Link>
                        )}
                        <Link
                            href={states()}
                            className="rounded hover:underline focus-visible:outline-2"
                        >
                            Explorar o Brasil
                        </Link>
                        <Link
                            href={myLand()}
                            className="rounded hover:underline focus-visible:outline-2"
                        >
                            Minha terra
                        </Link>
                        {can.accessAdministration && (
                            <Link
                                href={administration()}
                                className="rounded hover:underline focus-visible:outline-2"
                            >
                                Administração
                            </Link>
                        )}
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="flex items-center gap-1 rounded border border-current/30 px-4 py-2"
                            >
                                Minha conta{' '}
                                <ArrowUpRight
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded hover:underline"
                                >
                                    Entrar
                                </Link>
                                <Link
                                    href={register()}
                                    className="rounded bg-[#243e35] px-4 py-2 text-white dark:bg-[#eee9dc] dark:text-[#15251f]"
                                >
                                    Fazer parte
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main
                id="conteudo"
                className="mx-auto max-w-7xl px-6 py-12 lg:px-12 lg:py-16"
            >
                {children}
            </main>
            <footer className="mx-auto flex max-w-7xl flex-wrap justify-between gap-4 border-t border-current/15 px-6 py-8 text-sm lg:px-12">
                <p>Nossas raízes seguem conosco.</p>
                <p>Do Sul, com lugar para todo o Brasil.</p>
            </footer>
        </div>
    );
}
