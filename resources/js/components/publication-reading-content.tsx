import type { PublicStory } from '@/types/editorial';

const poetrySlugs = new Set(['poema', 'poesia', 'verso', 'versos']);

export default function PublicationReadingContent({
    story,
}: {
    story: PublicStory;
}) {
    const paragraphs = story.body?.split(/\n\s*\n/).filter(Boolean) ?? [];
    const isPoetry = [
        story.category?.slug,
        ...story.tags.map((tag) => tag.slug),
    ]
        .filter((slug): slug is string => Boolean(slug))
        .some((slug) => poetrySlugs.has(slug));

    if (isPoetry) {
        return (
            <section
                aria-label="Leitura do poema"
                className="mx-auto max-w-3xl rounded-2xl border border-public-rule bg-public-surface px-6 py-8 sm:px-10 sm:py-10"
            >
                <p className="public-eyebrow mb-6">Leitura em versos</p>
                <div className="space-y-7 font-editorial text-xl leading-[1.9] text-public-ink sm:text-2xl">
                    {paragraphs.map((stanza, index) => (
                        <p className="whitespace-pre-wrap" key={index}>
                            {stanza}
                        </p>
                    ))}
                </div>
            </section>
        );
    }

    if (story.type === 'causo') {
        return (
            <section
                aria-label="Apresentação do causo"
                className="mx-auto max-w-3xl border-l-2 border-public-leather pl-5 sm:pl-8"
            >
                <p className="public-eyebrow mb-5">Um causo</p>
                <div className="space-y-7 font-editorial text-xl leading-[1.8] text-public-ink sm:text-[1.35rem]">
                    {paragraphs.map((paragraph, index) => (
                        <p className="whitespace-pre-wrap" key={index}>
                            {paragraph}
                        </p>
                    ))}
                </div>
            </section>
        );
    }

    return (
        <div className="mx-auto max-w-3xl space-y-7 font-editorial text-xl leading-[1.8] text-public-ink sm:text-[1.35rem]">
            {paragraphs.map((paragraph, index) => (
                <p className="whitespace-pre-wrap" key={index}>
                    {paragraph}
                </p>
            ))}
        </div>
    );
}
