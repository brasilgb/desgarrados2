import { Link } from '@inertiajs/react';
export default function EditorialPagination({
    links,
}: {
    links: { url: string | null; label: string; active: boolean }[];
}) {
    if (links.length <= 3) return null;
    return (
        <nav aria-label="Paginação" className="flex flex-wrap gap-3 py-4">
            {links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        aria-current={link.active ? 'page' : undefined}
                        className={`rounded-lg border px-3 py-2 ${link.active ? 'bg-muted' : ''}`}
                    >
                        {link.label
                            .replace(/&laquo;|&raquo;/g, '')
                            .replace('Previous', 'Anterior')
                            .replace('Next', 'Próxima')
                            .trim()}
                    </Link>
                ) : (
                    <span key={i} className="px-3 py-2 opacity-50">
                        {link.label
                            .replace(/&laquo;|&raquo;/g, '')
                            .replace('Previous', 'Anterior')
                            .replace('Next', 'Próxima')
                            .trim()}
                    </span>
                ),
            )}
        </nav>
    );
}
