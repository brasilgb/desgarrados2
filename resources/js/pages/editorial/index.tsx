import { Head, Link, useForm } from '@inertiajs/react';
import { Lookup, actionClass, fieldClass } from '@/components/editorial-fields';
import TerritoryPagination from '@/components/editorial-pagination';
import { create, index, show, taxonomy } from '@/routes/editorial';
import type { Page } from '@/types/editorial';
import { labels } from '@/types/editorial';
type Row = {
    id: number;
    title: string | null;
    slug: string;
    status: string;
    type: string;
    author: string | null;
    version: number;
    scheduled_for: string | null;
};
export default function Editorial({
    publications,
    filters,
    types,
    statuses,
    canManage,
}: {
    publications: Page<Row>;
    filters: Record<string, string>;
    types: string[];
    statuses: string[];
    canManage: boolean;
}) {
    const form = useForm({
        status: filters.status ?? '',
        type: filters.type ?? '',
        author_id: filters.author_id ?? '',
        category_id: filters.category_id ?? '',
        municipality_id: filters.municipality_id ?? '',
        region_id: filters.region_id ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    return (
        <section className="space-y-6 p-6">
            <Head title="Redação">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p className="text-sm text-muted-foreground">
                        Desgarrados · núcleo editorial
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Histórias em construção
                    </h1>
                </div>
                <Link className={actionClass} href={create()}>
                    Nova publicação
                </Link>
            </div>
            <p>
                Da memória à página pública, com revisão e cuidado em cada
                versão.
            </p>
            {canManage && (
                <nav className="flex gap-4" aria-label="Taxonomia">
                    <Link href={taxonomy('categories')}>Categorias</Link>
                    <Link href={taxonomy('tags')}>Tags</Link>
                </nav>
            )}
            <form
                className="grid gap-4 rounded-xl border p-4 md:grid-cols-2 xl:grid-cols-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.get(index().url);
                }}
            >
                {(['status', 'type'] as const).map((key) => (
                    <label key={key} className="space-y-2">
                        <span>
                            {key === 'status' ? 'Estado editorial' : 'Tipo'}
                        </span>
                        <select
                            className={fieldClass}
                            value={form.data[key]}
                            onChange={(e) => form.setData(key, e.target.value)}
                        >
                            <option value="">Todos</option>
                            {(key === 'status' ? statuses : types).map(
                                (value) => (
                                    <option key={value} value={value}>
                                        {labels[value]}
                                    </option>
                                ),
                            )}
                        </select>
                    </label>
                ))}
                {(
                    [
                        'author_id',
                        'category_id',
                        'municipality_id',
                        'region_id',
                    ] as const
                ).map((key) => (
                    <div key={key}>
                        <Lookup
                            kind={
                                {
                                    author_id: 'authors',
                                    category_id: 'categories',
                                    municipality_id: 'municipalities',
                                    region_id: 'regions',
                                }[key]
                            }
                            label={
                                {
                                    author_id: 'Autor',
                                    category_id: 'Categoria',
                                    municipality_id: 'Município',
                                    region_id: 'Região',
                                }[key]
                            }
                            selected={
                                form.data[key]
                                    ? [
                                          {
                                              id: Number(form.data[key]),
                                              name: `Filtro #${form.data[key]}`,
                                          },
                                      ]
                                    : []
                            }
                            onChange={(items) =>
                                form.setData(key, String(items[0]?.id ?? ''))
                            }
                        />
                    </div>
                ))}
                {(['from', 'to'] as const).map((key) => (
                    <label key={key} className="space-y-2">
                        <span>
                            {key === 'from'
                                ? 'Criada a partir de'
                                : 'Criada até'}
                        </span>
                        <input
                            type="date"
                            className={fieldClass}
                            value={form.data[key]}
                            onChange={(e) => form.setData(key, e.target.value)}
                        />
                    </label>
                ))}
                <button className={actionClass} disabled={form.processing}>
                    {form.processing ? 'Filtrando…' : 'Filtrar'}
                </button>
                <Link className={actionClass} href={index()}>
                    Limpar filtros
                </Link>
            </form>
            {publications.data.length === 0 ? (
                <p className="rounded-xl border border-dashed p-12 text-center">
                    Nenhuma publicação encontrada. Comece com uma memória da sua
                    terra.
                </p>
            ) : (
                <div className="grid gap-4 lg:grid-cols-2">
                    {publications.data.map((item) => (
                        <Link
                            key={item.id}
                            href={show(item.id)}
                            className="space-y-3 rounded-xl border p-6 hover:bg-accent"
                        >
                            <div className="flex flex-wrap justify-between gap-2">
                                <span className="text-sm text-muted-foreground">
                                    {labels[item.type]}
                                </span>
                                <span className="rounded-full bg-muted px-3 py-1 text-sm">
                                    {labels[item.status]}
                                </span>
                            </div>
                            <h2 className="text-xl font-semibold">
                                {item.title ?? item.slug}
                            </h2>
                            <p className="text-sm break-all text-muted-foreground">
                                /historias/{item.slug}
                            </p>
                            <p className="text-sm">
                                {item.author ?? 'Autoria preservada'} · versão{' '}
                                {item.version}
                            </p>
                            {item.scheduled_for && (
                                <p className="text-sm">
                                    Agendada para{' '}
                                    {new Date(
                                        item.scheduled_for,
                                    ).toLocaleString('pt-BR', {
                                        timeZone: 'America/Sao_Paulo',
                                    })}
                                </p>
                            )}
                        </Link>
                    ))}
                </div>
            )}
            <TerritoryPagination links={publications.links} />
        </section>
    );
}
