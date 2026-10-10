import { Head, Link } from '@inertiajs/react';
import TerritoryPagination from '@/components/editorial-pagination';
import StoryFigure from '@/components/story-figure';
import { index, section as sectionRoute, show } from '@/routes/stories';
import type { Page, PublicStory } from '@/types/editorial';
import { labels } from '@/types/editorial';
export default function Feed({
    publications,
    sections,
    section,
}: {
    publications: Page<PublicStory>;
    sections: string[];
    section: string | null;
}) {
    return (
        <section className="space-y-10">
            <Head
                title={section ? labels[section] : 'Histórias da nossa terra'}
            >
                <meta
                    name="description"
                    content="Memórias, causos, pessoas e cultura. Histórias que nos ligam à nossa terra."
                />
            </Head>
            <header className="max-w-3xl space-y-5">
                <p className="text-sm tracking-widest uppercase">
                    Nossas raízes seguem conosco
                </p>
                <h1 className="font-serif text-5xl leading-tight md:text-7xl">
                    {section ? labels[section] : 'Histórias da nossa terra'}
                </h1>
                <p className="text-xl opacity-80">
                    Lembranças, pessoas e lugares que continuam fazendo parte de
                    nós.
                </p>
            </header>
            {sections.length > 0 && (
                <nav
                    aria-label="Seções editoriais"
                    className="flex flex-wrap gap-5 border-y border-current/15 py-4"
                >
                    <Link href={index()}>Todas</Link>
                    {sections.map((slug) => (
                        <Link
                            key={slug}
                            className="underline"
                            href={sectionRoute(slug)}
                        >
                            {labels[slug]}
                        </Link>
                    ))}
                </nav>
            )}
            {publications.data.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-current/30 p-12">
                    <h2 className="font-serif text-2xl">
                        As primeiras histórias estão a caminho.
                    </h2>
                    <p className="mt-3">
                        Estamos preparando este espaço com cuidado editorial.
                    </p>
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
