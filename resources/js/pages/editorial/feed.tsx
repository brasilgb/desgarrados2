import { Head, Link, useForm } from '@inertiajs/react';
import TerritoryPagination from '@/components/editorial-pagination';
import StoryFigure from '@/components/story-figure';
import { index, section as sectionRoute, show } from '@/routes/stories';
import { index as territories } from '@/routes/territory';
import type { Page, PublicStory } from '@/types/editorial';
import { labels } from '@/types/editorial';
export default function Feed({
    publications,
    sections,
    section,
    categories,
    tags,
    municipalities,
    regions,
    filters,
    canonical,
    ogImage,
}: {
    publications: Page<PublicStory>;
    sections: string[];
    section: string | null;
    categories: { id: number; name: string; slug: string }[];
    tags: { id: number; name: string; slug: string }[];
    municipalities: {
        id: number;
        name: string;
        state: { abbreviation: string };
    }[];
    regions: { id: number; name: string }[];
    filters: {
        category?: string;
        tag?: string;
        municipality?: string;
        region?: string;
    };
    canonical: string;
    ogImage: { src: string; width: number; height: number } | null;
}) {
    const filter = useForm({
        category: filters.category ?? '',
        tag: filters.tag ?? '',
        municipality: filters.municipality ?? '',
        region: filters.region ?? '',
    });

    return (
        <section className="space-y-10">
            <Head
                title={
                    section ? labels[section] : 'Acervo cultural e territorial'
                }
            >
                <meta
                    name="description"
                    content="Memórias, causos, pessoas e expressões culturais ligadas aos territórios."
                />
                <link rel="canonical" href={canonical} />
                <meta
                    property="og:title"
                    content={
                        section
                            ? labels[section]
                            : 'Acervo cultural e territorial'
                    }
                />
                <meta
                    property="og:description"
                    content="Memórias, causos, pessoas e expressões culturais ligadas aos territórios."
                />
                {ogImage && (
                    <meta
                        property="og:image"
                        content={new URL(ogImage.src, canonical).toString()}
                    />
                )}
            </Head>
            <header className="max-w-3xl space-y-5">
                <p className="public-eyebrow">Nossas raízes seguem conosco</p>
                <h1 className="font-editorial text-5xl leading-tight tracking-tight text-public-ink md:text-7xl">
                    {section
                        ? labels[section]
                        : 'Acervo cultural e territorial'}
                </h1>
                <p className="max-w-2xl text-lg leading-relaxed text-public-muted sm:text-xl">
                    Memórias, causos, pessoas e expressões culturais ligadas aos
                    lugares que continuam fazendo parte de nós.
                </p>
            </header>
            {sections.length > 0 && (
                <nav
                    aria-label="Seções editoriais"
                    className="flex flex-wrap gap-2 border-y border-public-rule py-4"
                >
                    <Link className="public-secondary-link" href={index()}>
                        Todo o acervo
                    </Link>
                    {sections.map((slug) => (
                        <Link
                            key={slug}
                            className="public-secondary-link"
                            href={sectionRoute(slug)}
                        >
                            {labels[slug]}
                        </Link>
                    ))}
                </nav>
            )}
            <form
                className="grid gap-4 rounded-2xl border border-public-rule bg-public-surface p-5 sm:grid-cols-2 xl:grid-cols-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    filter.get(
                        section ? sectionRoute(section).url : index().url,
                        { preserveState: true, replace: true },
                    );
                }}
            >
                <label className="space-y-2 text-sm font-medium text-public-ink">
                    <span>Tema</span>
                    <select
                        className="public-focus min-h-11 w-full rounded-xl border border-public-rule bg-public-paper px-3 py-2 text-public-ink"
                        value={filter.data.category}
                        onChange={(event) =>
                            filter.setData('category', event.target.value)
                        }
                    >
                        <option value="">Todos os temas</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.slug}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="space-y-2 text-sm font-medium text-public-ink">
                    <span>Expressão</span>
                    <select
                        className="public-focus min-h-11 w-full rounded-xl border border-public-rule bg-public-paper px-3 py-2 text-public-ink"
                        value={filter.data.tag}
                        onChange={(event) =>
                            filter.setData('tag', event.target.value)
                        }
                    >
                        <option value="">Todas as expressões</option>
                        {tags.map((tag) => (
                            <option key={tag.id} value={tag.slug}>
                                {tag.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="space-y-2 text-sm font-medium text-public-ink">
                    <span>Município</span>
                    <select
                        className="public-focus min-h-11 w-full rounded-xl border border-public-rule bg-public-paper px-3 py-2 text-public-ink"
                        value={filter.data.municipality}
                        onChange={(event) =>
                            filter.setData('municipality', event.target.value)
                        }
                    >
                        <option value="">Todos os municípios</option>
                        {municipalities.map((municipality) => (
                            <option
                                key={municipality.id}
                                value={municipality.id}
                            >
                                {municipality.name} ·{' '}
                                {municipality.state.abbreviation}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="space-y-2 text-sm font-medium text-public-ink">
                    <span>Região</span>
                    <select
                        className="public-focus min-h-11 w-full rounded-xl border border-public-rule bg-public-paper px-3 py-2 text-public-ink"
                        value={filter.data.region}
                        onChange={(event) =>
                            filter.setData('region', event.target.value)
                        }
                    >
                        <option value="">Todas as regiões</option>
                        {regions.map((region) => (
                            <option key={region.id} value={region.id}>
                                {region.name}
                            </option>
                        ))}
                    </select>
                </label>
                <div className="flex flex-wrap gap-3 sm:col-span-2 xl:col-span-4">
                    <button
                        className="public-primary-link"
                        disabled={filter.processing}
                    >
                        {filter.processing ? 'Filtrando…' : 'Aplicar filtros'}
                    </button>
                    <Link
                        href={section ? sectionRoute(section) : index()}
                        className="public-secondary-link"
                    >
                        Limpar filtros
                    </Link>
                </div>
            </form>
            {publications.data.length === 0 ? (
                <div className="rounded-2xl border border-public-rule bg-public-surface p-6 sm:p-8">
                    <p className="public-eyebrow">Acervo em construção</p>
                    <h2 className="mt-2 font-editorial text-3xl text-public-ink">
                        Este caminho ainda está sendo preparado.
                    </h2>
                    <p className="mt-3 max-w-2xl leading-relaxed text-public-muted">
                        A curadoria reúne narrativas com contexto e cuidado.
                        Enquanto isso, explore outros caminhos ou encontre
                        lembranças ligadas a um território.
                    </p>
                    <div className="mt-5 flex flex-wrap gap-3">
                        <Link href={index()} className="public-primary-link">
                            Explorar o acervo
                        </Link>
                        <Link
                            href={territories()}
                            className="public-secondary-link"
                        >
                            Descobrir territórios
                        </Link>
                    </div>
                </div>
            ) : (
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {publications.data.map((p) => (
                        <article
                            key={p.slug}
                            className="flex flex-col gap-4 rounded-2xl border border-current/20 p-7"
                        >
                            {p.cover && (
                                <StoryFigure
                                    media={p.cover}
                                    sizes="(min-width: 1024px) 22rem, (min-width: 768px) 45vw, 100vw"
                                    showCaption={false}
                                    className="-mx-2 -mt-2"
                                />
                            )}
                            <p className="text-xs tracking-widest uppercase">
                                {labels[p.type]}
                                {p.municipality && ` · ${p.municipality.name}`}
                            </p>
                            <h2 className="font-serif text-3xl leading-tight">
                                <Link
                                    href={show(p.slug)}
                                    className="hover:underline"
                                >
                                    {p.title}
                                </Link>
                            </h2>
                            {p.summary && (
                                <p className="leading-relaxed opacity-80">
                                    {p.summary}
                                </p>
                            )}
                            <p className="mt-auto text-sm">
                                {p.byline}
                                {p.byline && ' · '}
                                <time dateTime={p.published_at}>
                                    {new Date(
                                        p.published_at,
                                    ).toLocaleDateString('pt-BR', {
                                        timeZone: 'America/Sao_Paulo',
                                    })}
                                </time>
                            </p>
                        </article>
                    ))}
                </div>
            )}
            <TerritoryPagination links={publications.links} />
        </section>
    );
}
