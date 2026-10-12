import { Link } from '@inertiajs/react';
import { ArrowRight, Compass } from 'lucide-react';
import { myLand } from '@/routes/territory';

export default function TerritoryFinder({
    compact = false,
}: {
    compact?: boolean;
}) {
    return (
        <Link
            href={myLand()}
            className={`public-focus group flex min-h-16 items-center gap-4 rounded-2xl border border-public-rule bg-public-surface p-4 transition-colors hover:border-public-leather ${compact ? '' : 'sm:p-5'}`}
        >
            <span className="grid size-11 shrink-0 place-items-center rounded-full bg-public-mate text-public-paper">
                <Compass className="size-5" aria-hidden="true" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block font-semibold text-public-ink">
                    Encontrar minha terra
                </span>
                <span className="mt-0.5 block text-sm text-public-muted">
                    Escolha um estado e busque um município.
                </span>
            </span>
            <ArrowRight
                className="size-5 shrink-0 text-public-leather transition-transform group-hover:translate-x-1"
                aria-hidden="true"
            />
        </Link>
    );
}
