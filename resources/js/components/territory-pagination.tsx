import { Link } from '@inertiajs/react';

export function TerritoryPagination({
    current,
    last,
    pageUrl,
}: {
    current: number;
    last: number;
    pageUrl: (page: number) => string;
}) {
    if (last <= 1) return null;
    return (
        <nav
            aria-label="Paginação"
            className="flex flex-wrap items-center gap-5 py-6 text-sm"
        >
            {current > 1 && (
                <Link
                    href={pageUrl(current - 1)}
                    className="rounded border border-current/30 px-4 py-2"
                >
                    Anterior
                </Link>
            )}
            <span>
                Página {current} de {last}
            </span>
            {current < last && (
                <Link
                    href={pageUrl(current + 1)}
                    className="rounded border border-current/30 px-4 py-2"
                >
                    Próxima
                </Link>
            )}
        </nav>
    );
}
