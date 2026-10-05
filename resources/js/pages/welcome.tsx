import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Compass, MapPin, Sprout } from 'lucide-react';
import { index, myLand, state } from '@/routes/territory';
import type { TerritoryState } from '@/types/territory';

export default function Welcome({
    featuredState,
    stateCount,
    municipalityCount,
}: {
    featuredState: TerritoryState | null;
    stateCount: number;
    municipalityCount: number;
}) {
    return (
        <>
            <Head title="Nossas raízes seguem conosco">
                <meta
                    name="description"
                    content="Encontre sua terra, explore municípios e mantenha por perto as raízes que fazem parte da sua história."
                />
            </Head>
            <section className="grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
                <div className="flex flex-col gap-7">
                    <p className="text-xs font-semibold tracking-[0.24em] uppercase">
                        Pertencer vai além de permanecer
                    </p>
                    <h1 className="max-w-2xl font-serif text-5xl leading-[1.06] tracking-tight sm:text-6xl lg:text-7xl">
                        Nossas raízes
                        <br />
                        seguem{' '}
                        <span className="text-[#9d4934] italic dark:text-[#e3a089]">
                            conosco.
                        </span>
                    </h1>
                    <p className="max-w-lg text-lg leading-relaxed opacity-85">
                        A gente pode mudar de cidade. Mas leva junto as
                        paisagens, os costumes e os lugares que ensinam quem a
                        gente é.
                    </p>
                    <div className="flex flex-wrap gap-4">
                        <Link
                            href={myLand()}
                            className="flex items-center gap-3 rounded-full bg-[#243e35] px-6 py-3 font-medium text-white dark:bg-[#eee9dc] dark:text-[#15251f]"
                        >
                            Encontrar minha terra{' '}
                            <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                        <Link
                            href={index()}
                            className="rounded-full border border-current/30 px-6 py-3 font-medium"
                        >
                            Explorar o Brasil
                        </Link>
                    </div>
                    <p className="text-sm opacity-75">
                        Para quem partiu. Para quem ficou. Para quem quer
                        conhecer.
                    </p>
                </div>
                <div className="relative overflow-hidden rounded-t-[45%] rounded-b-3xl bg-[#ded8bd] dark:bg-[#344f43]">
                    <svg
                        viewBox="0 0 520 540"
                        role="img"
                        aria-labelledby="landscape-title"
                        className="w-full"
                    >
                        <title id="landscape-title">
                            Ilustração de campos, colinas e caminhos do Sul
                        </title>
                        <circle cx="359" cy="137" r="50" fill="#c48452" />
                        <path
                            d="M0 325 Q140 185 295 296 T520 263 V540 H0Z"
                            fill="#879174"
                        />
                        <path
                            d="M0 386 Q180 270 340 366 T520 316 V540 H0Z"
                            fill="#55745e"
                        />
                        <path
                            d="M0 455 Q160 361 310 419 T520 377 V540 H0Z"
                            fill="#294b3e"
                        />
                        <path
                            d="M282 540 Q110 469 252 403 Q350 355 295 296"
                            fill="none"
                            stroke="#d8c6a2"
                            strokeWidth="20"
                        />
                        <path
                            d="M60 414 V351 M40 364 L60 351 L80 364 M437 405 V342 M417 355 L437 342 L457 355"
                            fill="none"
                            stroke="#243e35"
                            strokeWidth="5"
                        />
                    </svg>
                    <div className="absolute right-6 bottom-6 left-6 flex items-center justify-between rounded-xl bg-[#f5f2e9]/95 px-5 py-4 text-[#243e35]">
                        <span className="text-sm">
                            Uma terra. Muitas histórias.
                        </span>
                        <Sprout className="size-5" aria-hidden="true" />
                    </div>
                </div>
            </section>
            <section
                className="mt-16 grid gap-8 border-t border-current/20 pt-10 md:grid-cols-3"
                aria-label="Descubra o território"
            >
                <div className="flex flex-col gap-3">
                    <Compass className="size-6" aria-hidden="true" />
                    <h2 className="text-xl font-semibold">
                        Um Brasil de pertencimentos
                    </h2>
                    <p className="text-sm leading-relaxed opacity-80">
                        {stateCount} estados e{' '}
                        {municipalityCount.toLocaleString('pt-BR')} municípios
                        para descobrir no nosso território.
                    </p>
                    <Link
                        href={index()}
                        className="text-sm font-semibold underline underline-offset-4"
                    >
                        Conhecer os estados
                    </Link>
                </div>
                <div className="flex flex-col gap-3">
                    <MapPin className="size-6" aria-hidden="true" />
                    <h2 className="text-xl font-semibold">
                        Começamos pelo Rio Grande
                    </h2>
                    <p className="text-sm leading-relaxed opacity-80">
                        O Sul é nosso ponto de partida. O vínculo com a terra
                        não conhece fronteiras.
                    </p>
                    {featuredState && (
                        <Link
                            href={state(featuredState.slug)}
                            className="text-sm font-semibold underline underline-offset-4"
                        >
                            Explorar o Rio Grande do Sul
                        </Link>
                    )}
                </div>
                <div className="flex flex-col gap-3">
                    <Sprout className="size-6" aria-hidden="true" />
                    <h2 className="text-xl font-semibold">
                        Sua terra, sua escolha
                    </h2>
                    <p className="text-sm leading-relaxed opacity-80">
                        Escolha um lugar para explorar. Essa escolha não altera
                        seu perfil nem divulga onde você vive.
                    </p>
                    <Link
                        href={myLand()}
                        className="text-sm font-semibold underline underline-offset-4"
                    >
                        Encontrar minha terra
                    </Link>
                </div>
            </section>
        </>
    );
}
