import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, MapPin } from 'lucide-react';
import PublicationReadingContent from '@/components/publication-reading-content';
import StoryFigure from '@/components/story-figure';
import { home } from '@/routes';
import { index } from '@/routes/stories';
import type { PublicStory } from '@/types/editorial';
import { labels } from '@/types/editorial';
export default function Publication({
    publication: p,
    canonical,
    ogImage,
    preview = false,
}: {
    publication: PublicStory;
    canonical: string;
    ogImage: { url: string; width: number; height: number } | null;
    preview?: boolean;
}) {
    return (
        <article className="mx-auto max-w-4xl space-y-8 sm:space-y-10">
            <Head title={p.title}>
                {preview ? (
                    <meta name="robots" content="noindex,nofollow" />
                ) : (
                    <link rel="canonical" href={canonical} />
                )}
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
            {preview && (
                <p
                    role="status"
                    className="rounded-lg border border-public-leather p-4"
                >
                    Pré-visualização privada — esta revisão ainda não foi
                    publicada.
                </p>
            )}
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
                    href={index()}
                    className="public-focus hover:text-public-leather"
                >
                    Acervo
                </Link>
                <span aria-hidden="true">/</span>
                <span
                    aria-current="page"
                    className="line-clamp-1 font-medium text-public-ink"
                >
                    {p.title}
                </span>
            </nav>
            <header className="space-y-5">
                <p className="public-eyebrow">
                    {labels[p.type]}
                    {p.category && ` · ${p.category.name}`}
                </p>
                <h1 className="max-w-[18ch] font-editorial text-4xl leading-[1.04] tracking-tight text-public-ink sm:text-5xl md:text-6xl">
                    {p.title}
                </h1>
                {p.summary && (
                    <p className="max-w-3xl text-xl leading-relaxed text-public-muted">
                        {p.summary}
                    </p>
                )}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-public-rule py-4 text-sm text-public-muted">
                    {p.byline && <span>Por {p.byline}</span>}
                    <time dateTime={p.published_at}>
                        {new Date(p.published_at).toLocaleDateString('pt-BR', {
                            timeZone: 'America/Sao_Paulo',
                        })}
                    </time>
                    {p.municipality && (
                        <Link
                            className="public-focus underline decoration-public-rule underline-offset-4 hover:text-public-leather"
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
                    className="overflow-hidden rounded-3xl"
                />
            )}
            <PublicationReadingContent story={p} />
            {!!p.images?.length && (
                <div className="mx-auto max-w-3xl space-y-10">
                    {p.images.map((media) => (
                        <StoryFigure
                            key={media.image?.src}
                            media={media}
                            sizes="(min-width: 768px) 48rem, 100vw"
                        />
                    ))}
                </div>
            )}
            <footer className="space-y-7 border-t border-public-rule pt-7">
                <div className="flex flex-wrap gap-3">
                    {p.regions.map((region) => (
                        <Link
                            className="public-focus inline-flex min-h-10 items-center gap-2 rounded-full border border-public-rule px-4 py-2 text-sm font-medium hover:border-public-leather hover:text-public-leather"
                            key={region.slug}
                            href={`/regioes/${region.slug}`}
                        >
                            <MapPin className="size-4" aria-hidden="true" />
                            {region.name}
                        </Link>
                    ))}
                    {p.tags.map((tag) => (
                        <span
                            key={tag.slug}
                            className="inline-flex min-h-10 items-center rounded-full border border-public-rule px-4 py-2 text-sm text-public-muted"
                        >
                            {tag.name}
                        </span>
                    ))}
                </div>
                {!!p.sources?.length && (
                    <section className="space-y-3">
                        <h2 className="font-editorial text-2xl text-public-ink">
                            Fontes e referências
                        </h2>
                        <ul className="space-y-3 text-sm text-public-muted">
                            {p.sources.map((source, i) => (
                                <li key={i}>
                                    {source.url ? (
                                        <a
                                            className="public-focus underline underline-offset-4 hover:text-public-leather"
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
                {p.regions.length > 0 && (
                    <section
                        aria-labelledby="related-stories"
                        className="rounded-2xl bg-public-mate p-6 text-public-paper sm:p-8"
                    >
                        <p className="public-eyebrow !text-[#e2aa88]">
                            Continue por perto
                        </p>
                        <h2
                            id="related-stories"
                            className="mt-2 font-editorial text-3xl"
                        >
                            Mais histórias das mesmas regiões
                        </h2>
                        <p className="mt-2 max-w-2xl text-public-paper/80">
                            Siga os vínculos territoriais desta publicação para
                            encontrar outros conteúdos aprovados.
                        </p>
                        <div className="mt-5 flex flex-wrap gap-3">
                            {p.regions.map((region) => (
                                <Link
                                    key={region.slug}
                                    href={index({
                                        query: { region: region.id },
                                    })}
                                    className="public-focus inline-flex min-h-11 items-center gap-2 rounded-full border border-public-paper/40 px-4 py-2 font-semibold hover:bg-public-paper/10"
                                >
                                    {region.name}
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
                <Link
                    href={index()}
                    className="public-focus inline-flex min-h-11 items-center gap-2 font-semibold text-public-leather hover:underline"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Todo o acervo
                </Link>
            </footer>
        </article>
    );
}
