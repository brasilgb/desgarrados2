import { Leaf } from 'lucide-react';

export default function PublicEmptyState({
    title,
    children,
}: {
    title: string;
    children: string;
}) {
    return (
        <div
            className="rounded-2xl border border-dashed border-public-rule bg-public-surface/50 px-6 py-8 sm:px-9 sm:py-10"
            role="status"
        >
            <Leaf
                className="mb-4 size-5 text-public-leather"
                aria-hidden="true"
            />
            <h3 className="font-editorial text-2xl text-public-ink sm:text-3xl">
                {title}
            </h3>
            <p className="mt-3 max-w-2xl leading-relaxed text-public-muted">
                {children}
            </p>
        </div>
    );
}
