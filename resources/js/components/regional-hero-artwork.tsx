import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { myLand } from '@/routes/territory';

export default function RegionalHeroArtwork() {
    return (
        <figure className="regional-artwork relative m-0 overflow-hidden rounded-[1.75rem] border border-public-rule bg-public-surface">
            <svg
                className="block aspect-[1.18/1] w-full sm:aspect-[1.35/1] lg:aspect-[1.08/1]"
                viewBox="0 0 800 680"
                role="img"
                aria-labelledby="regional-artwork-title regional-artwork-desc"
            >
                <title id="regional-artwork-title">
                    Paisagem gráfica de caminhos e campo
                </title>
                <desc id="regional-artwork-desc">
                    Ilustração vetorial original do horizonte sulino, sem
                    representar um local geográfico específico.
                </desc>
                <defs>
                    <linearGradient id="paper-sky" x2="0" y2="1">
                        <stop offset="0" stopColor="var(--public-paper)" />
                        <stop offset="1" stopColor="var(--public-surface)" />
                    </linearGradient>
                    <clipPath id="artwork-frame">
                        <rect width="800" height="680" rx="28" />
                    </clipPath>
                </defs>
                <g clipPath="url(#artwork-frame)">
                    <rect width="800" height="680" fill="url(#paper-sky)" />
                    <circle
                        cx="584"
                        cy="190"
                        r="89"
                        fill="var(--public-leather)"
                        opacity=".2"
                    />
                    <path
                        d="M0 391C114 352 190 358 294 393c128 43 207 25 506-24v311H0Z"
                        fill="var(--public-mate)"
                        opacity=".12"
                    />
                    <path
                        d="M0 467c132-70 249-69 363-18 147 66 256 15 437-38v269H0Z"
                        fill="var(--public-leather)"
                        opacity=".24"
                    />
                    <path
                        d="M0 532c184-80 304-40 422 5 114 43 219 28 378-29v172H0Z"
                        fill="var(--public-mate)"
                        opacity=".8"
                    />
                    <g
                        fill="none"
                        stroke="var(--public-wine)"
                        strokeLinecap="round"
                        opacity=".54"
                    >
                        <path d="M-45 442c105-49 213-53 323-17 112 36 220 39 382-12 67-21 119-27 175-24" />
                        <path d="M-30 462c109-50 210-50 310-17 112 37 222 41 390-9 63-19 115-25 167-21" />
                        <path d="M-9 482c101-45 196-47 296-15 117 38 233 42 397-5 61-18 106-23 147-21" />
                    </g>
                    <path
                        d="M102 680c24-83 94-136 174-176 70-35 147-47 212-80"
                        fill="none"
                        stroke="var(--public-paper)"
                        strokeWidth="32"
                        opacity=".95"
                    />
                    <path
                        d="M102 680c24-83 94-136 174-176 70-35 147-47 212-80"
                        fill="none"
                        stroke="var(--public-leather)"
                        strokeWidth="2"
                        strokeDasharray="2 12"
                        strokeLinecap="round"
                        opacity=".8"
                    />
                    <g
                        fill="none"
                        stroke="var(--public-mate)"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M674 493c-8-74 3-137 34-192" strokeWidth="4" />
                        <path
                            d="M704 354c-25-13-42-31-49-55 27 6 46 22 55 46"
                            strokeWidth="3"
                        />
                        <path
                            d="M693 382c19-24 39-37 62-40-8 25-25 42-52 51"
                            strokeWidth="3"
                        />
                        <path
                            d="M684 419c-24-14-39-32-45-57 27 7 44 23 51 48"
                            strokeWidth="3"
                        />
                        <path
                            d="M680 450c19-20 38-31 59-33-9 22-25 37-49 45"
                            strokeWidth="3"
                        />
                        <path d="M105 531c-3-44 3-81 21-112" strokeWidth="3" />
                        <path
                            d="M122 449c-15-8-25-19-29-34 16 4 27 14 32 29"
                            strokeWidth="2.5"
                        />
                        <path
                            d="M117 469c12-14 24-21 39-23-5 14-15 24-31 29"
                            strokeWidth="2.5"
                        />
                    </g>
                    <g fill="var(--public-wine)" opacity=".72">
                        <circle cx="180" cy="129" r="3" />
                        <circle cx="207" cy="111" r="2" />
                        <circle cx="233" cy="137" r="2.5" />
                        <circle cx="257" cy="119" r="1.8" />
                    </g>
                    <g
                        fill="none"
                        stroke="var(--public-wine)"
                        strokeWidth="2"
                        opacity=".7"
                    >
                        <path d="M92 92h46M115 69v46" />
                        <circle cx="115" cy="92" r="19" />
                    </g>
                </g>
            </svg>
            <figcaption className="flex flex-col gap-3 border-t border-public-rule bg-public-surface p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <span>
                    <span className="public-eyebrow block">
                        Um mapa de lembranças
                    </span>
                    <span className="mt-1 block text-sm text-public-muted">
                        Ilustração original; não representa lugar real.
                    </span>
                </span>
                <Link
                    href={myLand()}
                    className="public-focus inline-flex min-h-11 items-center gap-2 font-semibold text-public-leather hover:underline"
                >
                    Encontrar minha terra
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            </figcaption>
        </figure>
    );
}
