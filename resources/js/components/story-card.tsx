import { Link } from '@inertiajs/react';
import StoryFigure from '@/components/story-figure';
import { show } from '@/routes/stories';
import type { PublicStory } from '@/types/editorial';
import { labels } from '@/types/editorial';

export default function StoryCard({
    story,
    prominent = false,
}: {
    story: PublicStory;
    prominent?: boolean;
}) {
    const hasCover = Boolean(story.cover?.image);

    return (
        <article
            className={`group flex h-full flex-col overflow-hidden rounded-2xl border border-public-rule bg-public-surface transition-colors hover:border-public-leather ${prominent && hasCover ? 'md:grid md:grid-cols-[1.15fr_.85fr]' : ''}`}
        >
            {story.cover && (
                <StoryFigure
                    media={story.cover}
                    sizes={
                        prominent
                            ? '(min-width: 768px) 50vw, 100vw'
                            : '(min-width: 1024px) 22rem, 100vw'
                    }
                    showCaption={false}
                    priority={prominent}
                    className={prominent ? 'h-full' : ''}
                />
            )}
            <div
                className={`flex flex-1 flex-col items-start ${prominent ? 'justify-center p-6 sm:p-9' : 'p-5 sm:p-6'}`}
            >
                <p className="public-eyebrow mb-3">
                    {labels[story.type]}
                    {story.municipality && ` · ${story.municipality.name}`}
                </p>
                <h3
                    className={`font-editorial leading-[1.08] tracking-tight text-public-ink ${prominent ? 'text-4xl sm:text-5xl' : 'text-2xl sm:text-[1.75rem]'}`}
                >
                    <Link
                        href={show(story.slug)}
                        className="public-focus decoration-public-leather decoration-1 underline-offset-4 group-hover:underline"
                    >
                        {story.title}
                    </Link>
                </h3>
                {story.summary && (
                    <p className="mt-4 leading-relaxed text-public-muted">
                        {story.summary}
                    </p>
                )}
                <p className="mt-5 text-sm text-public-muted">
                    {story.byline}
                    {story.byline && ' · '}
                    <time dateTime={story.published_at}>
                        {new Date(story.published_at).toLocaleDateString(
                            'pt-BR',
                            { timeZone: 'America/Sao_Paulo' },
                        )}
                    </time>
                </p>
            </div>
        </article>
    );
}
