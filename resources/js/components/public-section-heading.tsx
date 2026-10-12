import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export default function PublicSectionHeading({
    eyebrow,
    title,
    href,
    label = 'Ver todos',
    id,
}: {
    eyebrow?: string;
    title: string;
    href?: string;
    label?: string;
    id?: string;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
            <div>
                {eyebrow && <p className="public-eyebrow mb-2">{eyebrow}</p>}
                <h2
                    id={id}
                    className="font-editorial text-3xl tracking-tight text-public-ink sm:text-4xl"
                >
                    {title}
                </h2>
            </div>
            {href && (
                <Link
                    href={href}
                    className="public-focus inline-flex min-h-11 items-center gap-2 pb-1 font-medium text-public-leather underline-offset-4 hover:underline"
                >
                    {label}
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            )}
        </div>
    );
}
