import type { MediaUsage } from '@/types/editorial';

/** Responsive editorial image: explicit dimensions avoid layout shift; the cover loads eagerly, the rest lazily. */
export default function StoryFigure({
    media,
    sizes,
    priority = false,
    showCaption = true,
    className = '',
}: {
    media: MediaUsage;
    sizes: string;
    priority?: boolean;
    showCaption?: boolean;
    className?: string;
}) {
    if (!media.image) return null;
    return (
        <figure className={`space-y-2 ${className}`}>
            <img
                src={media.image.src}
                srcSet={media.image.srcset}
                sizes={sizes}
                width={media.image.width}
                height={media.image.height}
                alt={media.alt}
                loading={priority ? 'eager' : 'lazy'}
                fetchPriority={priority ? 'high' : 'auto'}
                decoding="async"
                className="h-auto w-full rounded-2xl bg-current/5 object-cover"
            />
            {showCaption && (
                <figcaption className="text-sm opacity-80">
                    {media.caption && <span>{media.caption} </span>}
                    <span className="opacity-80">Foto: {media.credit}</span>
                </figcaption>
            )}
        </figure>
    );
}
