import { Head, Link } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import PublicEmptyState from '@/components/public-empty-state';
import PublicSectionHeading from '@/components/public-section-heading';
import StoryCard from '@/components/story-card';
import TerritoryPagination from '@/components/editorial-pagination';
import { home } from '@/routes';
import { index as stories } from '@/routes/stories';
import { index as states } from '@/routes/territory';
import type { Page, PublicStory } from '@/types/editorial';

type Municipality = {
    id: number;
    name: string;
    slug: string;
    state: { name: string; slug: string; abbreviation: string };
};

export default function RegionPage({
    region,
    municipalities,
    featured,
    recent,
    canonical,
    ogImage,
}: {
    region: {
        id: number;
        name: string;
        slug: string;
        kind: string;
        description: string | null;
    };
    municipalities: Page<Municipality>;
    featured: PublicStory[];
    recent: Page<PublicStory>;
    canonical: string;
    ogImage: { src: string; width: number; height: number } | null;
}) {
    const description =
        region.description ??
        `Histórias, memórias e municípios ligados à região ${region.name}.`;
    const regionStories = stories({ query: { region: region.id } }).url;

    return (
        <div className="space-y-14 sm:space-y-20">
            <Head title={region.name}>
                <meta name="description" content={description.slice(0, 160)} />
                <link rel="canonical" href={canonical} />
                <meta property="og:title" content={region.name} />
                <meta
                    property="og:description"
                    content={description.slice(0, 160)}
                />
                {ogImage && (
                    <meta
                        property="og:image"
                        content={new URL(ogImage.src, canonical).toString()}
                    />
                )}
            </Head>

            <nav
                aria-label="Você está em"
                className="flex flex-wrap items-center gap-2 text-sm text-public-muted"
            >
                <Link
                    href={home()}
                    className="public-focus hover:text-public-leather"
                >
                    Início
                </Link>
                <span aria-hidden="true">/</span>
                <Link
                    href={states()}
                    className="public-focus hover:text-public-leather"
                >
                    Territórios
                </Link>
                <span aria-hidden="true">/</span>
                <span
                    aria-current="page"
                    className="font-medium text-public-ink"
                >
                    {region.name}
                </span>
            </nav>

            <header className="relative overflow-hidden rounded-3xl border border-public-rule bg-public-surface px-6 py-9 sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                <div className="max-w-4xl">
                    <p className="public-eyebrow mb-4">
                        Região{' '}
                        {region.kind === 'cultural' ? 'cultural' : 'geográfica'}
                    </p>
                    <h1 className="max-w-[13ch] font-editorial text-5xl leading-[.98] tracking-tight text-public-ink sm:text-6xl lg:text-7xl">
                        {region.name}
                    </h1>
                    <p className="mt-6 max-w-3xl text-lg leading-relaxed text-public-muted sm:text-xl">
                        {description}
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <a href="#municipios" className="public-primary-link">
                            <MapPin className="size-4" aria-hidden="true" />
                            Conhecer os municípios
                        </a>
                        <a
                            href="#historias-regiao"
                            className="public-secondary-link"
                        >
                            Percorrer as histórias
                        </a>
                    </div>
                </div>
            </header>

            <nav
                aria-label="Nesta página"
                className="flex gap-2 overflow-x-auto border-b border-public-rule pb-3 text-sm"
            >
                <a
                    className="public-focus shrink-0 rounded-full bg-public-surface px-4 py-2 hover:text-public-leather"
                    href="#destaques"
                >
                    Destaques
                </a>
                <a
                    className="public-focus shrink-0 rounded-full bg-public-surface px-4 py-2 hover:text-public-leather"
                    href="#historias-regiao"
                >
                    Histórias
                </a>
                <a
                    className="public-focus shrink-0 rounded-full bg-public-surface px-4 py-2 hover:text-public-leather"
                    href="#municipios"
                >
                    Municípios
                </a>
            </nav>

            <section
                id="destaques"
                aria-labelledby="destaques-title"
                className="scroll-mt-6"
            >
                <PublicSectionHeading
                    id="destaques-title"
                    eyebrow="Curadoria regional"
                    title="Em destaque"
                    href={regionStories}
                    label="Mais histórias da região"
                />
                {featured.length ? (
                    <div className="space-y-5">
                        {featured.map((story, position) => (
                            <StoryCard
                                key={story.slug}
                                story={story}
                                prominent={position === 0}
                            />
                        ))}
                    </div>
                ) : (
                    <PublicEmptyState title="Ainda não há destaques nesta região.">
                        As publicações aparecem aqui quando forem selecionadas
                        pela curadoria. Enquanto isso, percorra as histórias
                        aprovadas abaixo.
                    </PublicEmptyState>
                )}
            </section>

            <section
                id="historias-regiao"
                aria-labelledby="historias-title"
                className="public-section scroll-mt-6"
            >
                <PublicSectionHeading
                    id="historias-title"
                    eyebrow="Vozes e lembranças locais"
                    title="Histórias e memórias"
                    href={regionStories}
                    label="Ver arquivo da região"
                />
                {recent.data.length ? (
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {recent.data.map((story) => (
                            <StoryCard key={story.slug} story={story} />
                        ))}
                    </div>
                ) : (
                    <PublicEmptyState title="As histórias desta região estão sendo preparadas.">
                        A curadoria publicará aqui apenas conteúdos revisados e
                        aprovados.
                    </PublicEmptyState>
                )}
                <div className="mt-5">
                    <TerritoryPagination links={recent.links} />
                </div>
            </section>

            <section
                id="municipios"
                aria-labelledby="municipios-title"
                className="public-section scroll-mt-6"
            >
                <PublicSectionHeading
                    id="municipios-title"
                    eyebrow="Geografia afetiva"
                    title="Municípios relacionados"
                />
                {municipalities.data.length ? (
                    <ul className="grid gap-px overflow-hidden rounded-2xl border border-public-rule bg-public-rule sm:grid-cols-2 xl:grid-cols-3">
                        {municipalities.data.map((municipality) => (
                            <li
                                key={municipality.id}
                                className="bg-public-paper"
                            >
                                <Link
                                    className="public-focus group flex min-h-16 items-center justify-between gap-3 p-4 transition-colors hover:bg-public-surface sm:p-5"
                                    href={`/estados/${municipality.state.slug}/municipios/${municipality.slug}`}
                                >
                                    <span>
                                        <span className="block font-semibold text-public-ink">
                                            {municipality.name}
                                        </span>
                                        <span className="mt-1 block text-sm text-public-muted">
                                            {municipality.state.name} ·{' '}
                                            {municipality.state.abbreviation}
                                        </span>
                                    </span>
                                    <ArrowRight
                                        className="size-4 shrink-0 text-public-leather transition-transform group-hover:translate-x-1"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <PublicEmptyState title="Os municípios desta região ainda estão em curadoria.">
                        As relações territoriais são atualizadas pela equipe
                        editorial sem alterar os dados oficiais dos municípios.
                    </PublicEmptyState>
                )}
                <div className="mt-5">
                    <TerritoryPagination links={municipalities.links} />
                </div>
            </section>

            <aside className="flex flex-col gap-4 rounded-2xl bg-public-mate p-6 text-public-paper sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <p className="public-eyebrow !text-[#e2aa88]">
                        Continue a descoberta
                    </p>
                    <h2 className="mt-2 font-editorial text-3xl">
                        Há outras terras esperando por você.
                    </h2>
                </div>
                <Link
                    href={states()}
                    className="public-focus inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-full border border-public-paper/40 px-5 py-2.5 font-semibold hover:bg-public-paper/10"
                >
                    Explorar territórios
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            </aside>
        </div>
    );
}
