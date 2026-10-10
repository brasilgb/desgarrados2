import { Head, Link } from '@inertiajs/react';
import StoryFigure from '@/components/story-figure';
import { index } from '@/routes/stories';
import type { PublicStory } from '@/types/editorial';
import { labels } from '@/types/editorial';
export default function Publication({
    publication: p,
    canonical,
    ogImage,
}: {
    publication: PublicStory;
    canonical: string;
    ogImage: { url: string; width: number; height: number } | null;
}) {
    return (
        <article className="mx-auto max-w-3xl space-y-8">
            <Head title={p.title}>
                <link rel="canonical" href={canonical} />
                <meta name="description" content={p.summary ?? p.title} />
                <meta property="og:title" content={p.title} />
                <meta
                    property="og:description"
                    content={p.summary ?? p.title}
                />
                <meta property="og:type" content="article" />
                <meta property="og:url" content={canonical} />
                {ogImage && <meta property="og:image" content={ogImage.url} />}
                {ogImage && (
                    <meta
                        property="og:image:width"
                        content={String(ogImage.width)}
                    />
                )}
                {ogImage && (
                    <meta
                        property="og:image:height"
                        content={String(ogImage.height)}
                    />
                )}
                {p.cover && (
                    <meta property="og:image:alt" content={p.cover.alt} />
                )}
            </Head>
            <Link href={index()} className="text-sm underline">
                ← Histórias da nossa terra
            </Link>
            <header className="space-y-5">
                <p className="text-sm tracking-widest uppercase">
                    {labels[p.type]}
                    {p.category && ` · ${p.category.name}`}
                </p>
                <h1 className="font-serif text-4xl leading-tight md:text-6xl">
                    {p.title}
                </h1>
                {p.summary && (
                    <p className="text-xl leading-relaxed opacity-80">
                        {p.summary}
                    </p>
                )}
                <div className="flex flex-wrap gap-3 border-y border-current/15 py-4 text-sm">
                    {p.byline && <span>Por {p.byline}</span>}
                    <time dateTime={p.published_at}>
                        {new Date(p.published_at).toLocaleDateString('pt-BR', {
                            timeZone: 'America/Sao_Paulo',
                        })}
                    </time>
                    {p.municipality && (
                        <Link
                            className="underline"
                            href={index({
                                query: { municipality: p.municipality.id },
                            })}
                        >
                            {p.municipality.name}
                        </Link>
                    )}
                </div>
            </header>
            {p.cover && (
                <StoryFigure
                    media={p.cover}
                    sizes="(min-width: 768px) 48rem, 100vw"
                    priority
                />
            )}
            <div className="space-y-6 font-serif text-xl leading-9">
                {p.body
                    ?.split(/\n\s*\n/)
                    .filter(Boolean)
                    .map((paragraph, i) => (
                        <p className="whitespace-pre-wrap" key={i}>
                            {paragraph}
                        </p>
                    ))}
            </div>
            {!!p.images?.length && (
                <div className="space-y-10">
                    {p.images.map((media) => (
                        <StoryFigure
                            key={media.image?.src}
                            media={media}
                            sizes="(min-width: 768px) 48rem, 100vw"
                        />
                    ))}
                </div>
            )}
            <footer className="space-y-5 border-t border-current/15 pt-6">
                <div className="flex flex-wrap gap-3">
                    {p.regions.map((region) => (
                        <Link
                            className="rounded-full border border-current/20 px-3 py-1 text-sm"
                            key={region.slug}
                            href={index({ query: { region: region.id } })}
                        >
                            {region.name}
                        </Link>
                    ))}
                    {p.tags.map((tag) => (
                        <span
                            key={tag.slug}
                            className="rounded-full border border-current/20 px-3 py-1 text-sm"
                        >
                            {tag.name}
                        </span>
                    ))}
                </div>
                {!!p.sources?.length && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Fontes e referências
                        </h2>
                        <ul className="space-y-2 text-sm">
                            {p.sources.map((source, i) => (
                                <li key={i}>
                                    {source.url ? (
                                        <a
                                            className="underline"
                                            href={source.url}
                                            rel="noopener noreferrer"
                                        >
                                            {source.title}
                                        </a>
                                    ) : (
                                        source.title
                                    )}
                                    {source.attribution &&
                                        ` — ${source.attribution}`}
                                    {source.accessed_at &&
                                        ` · acesso em ${source.accessed_at}`}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </footer>
        </article>
    );
}
