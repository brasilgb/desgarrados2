import { Link, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowUpRight, Compass, MapPin, Menu } from 'lucide-react';
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
        <div className="public-site">
            <a
                href="#conteudo"
                className="sr-only z-50 rounded bg-public-mate p-4 text-public-paper focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
            >
                Ir para o conteúdo
            </a>
            <header className="border-b border-public-rule bg-public-paper">
                <div className="public-content flex min-h-20 items-center justify-between gap-4 py-4 sm:py-5">
                    <Link
                        href={home()}
                        className="public-focus flex shrink-0 items-center gap-2.5"
                    >
                        <span className="grid size-10 place-items-center rounded-full bg-public-mate text-public-paper">
                            <MapPin className="size-5" aria-hidden="true" />
                        </span>
                        <span className="font-editorial text-2xl font-semibold tracking-tight sm:text-[1.7rem]">
                            desgarrados
                            <span className="text-public-leather">.</span>
                        </span>
                    </Link>
                    <nav
                        aria-label="Navegação principal"
                        className="hidden items-center gap-6 text-sm font-medium lg:flex"
                    >
                        <NavigationLinks
                            hasStories={hasStories}
                            canAdmin={can.accessAdministration}
                        />
                    </nav>
                    <div className="hidden items-center gap-3 lg:flex">
                        <Link href={myLand()} className="public-secondary-link">
                            <Compass className="size-4" aria-hidden="true" />
                            Buscar lugar
                        </Link>
                        <AccountLink user={auth.user} />
                    </div>
                    <details className="group relative lg:hidden">
                        <summary className="public-focus flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-full border border-public-rule px-4 py-2 font-medium marker:hidden [&::-webkit-details-marker]:hidden">
                            <Menu className="size-4" aria-hidden="true" />
                            Menu
                            <ArrowDown
                                className="size-4 transition-transform group-open:rotate-180"
                                aria-hidden="true"
                            />
                        </summary>
                        <div className="absolute top-[calc(100%+0.75rem)] right-0 z-30 w-[min(21rem,calc(100vw-2.5rem))] rounded-2xl border border-public-rule bg-public-surface p-4 shadow-xl">
                            <nav
                                aria-label="Navegação móvel"
                                className="flex flex-col gap-1"
                            >
                                <NavigationLinks
                                    hasStories={hasStories}
                                    canAdmin={can.accessAdministration}
                                    mobile
                                />
                            </nav>
                            <div className="mt-3 border-t border-public-rule pt-3">
                                <Link
                                    href={myLand()}
                                    className="public-primary-link w-full"
                                >
                                    <Compass
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Buscar lugar
                                </Link>
                                <div className="mt-2">
                                    <AccountLink user={auth.user} mobile />
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
            </header>
            <main
                id="conteudo"
                className="public-content py-10 sm:py-14 lg:py-16"
            >
                {children}
            </main>
            <footer className="border-t border-public-rule">
                <div className="public-content flex flex-wrap justify-between gap-3 py-7 text-sm text-public-muted">
                    <p className="m-0">Nossas raízes seguem conosco.</p>
                    <p className="m-0">Do Sul, com lugar para todo o Brasil.</p>
                </div>
            </footer>
        </div>
    );
}

function NavigationLinks({
    hasStories,
    canAdmin,
    mobile = false,
}: {
    hasStories: boolean;
    canAdmin: boolean;
    mobile?: boolean;
}) {
    const linkClass = mobile
        ? 'public-focus flex min-h-11 items-center rounded-xl px-3 font-medium hover:bg-public-paper'
        : 'public-focus py-2 text-public-ink hover:text-public-leather';

    return (
        <>
            {hasStories && (
                <Link href={stories()} className={linkClass}>
                    Acervo
                </Link>
            )}
            <Link href={states()} className={linkClass}>
                Territórios
            </Link>
            <Link href={myLand()} className={linkClass}>
                Minha terra
            </Link>
            {canAdmin && (
                <Link href={administration()} className={linkClass}>
                    Administração
                </Link>
            )}
        </>
    );
}

function AccountLink({
    user,
    mobile = false,
}: {
    user: User | null;
    mobile?: boolean;
}) {
    if (user) {
        return (
            <Link
                href={dashboard()}
                className={
                    mobile
                        ? 'public-focus flex min-h-11 items-center px-3 font-medium'
                        : 'public-secondary-link'
                }
            >
                Minha conta
                <ArrowUpRight className="size-4" aria-hidden="true" />
            </Link>
        );
    }

    return (
        <div className={mobile ? 'grid gap-1' : 'flex items-center gap-4'}>
            <Link
                href={login()}
                className={
                    mobile
                        ? 'public-focus flex min-h-11 items-center px-3 font-medium'
                        : 'public-focus py-2 font-medium'
                }
            >
                Entrar
            </Link>
            <Link
                href={register()}
                className={
                    mobile ? 'public-primary-link' : 'public-primary-link px-4'
                }
            >
                Fazer parte
            </Link>
        </div>
    );
}
