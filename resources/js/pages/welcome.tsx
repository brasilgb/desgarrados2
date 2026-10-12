import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    MapPin,
    MessageCircleMore,
    Sprout,
} from 'lucide-react';
import type { ReactNode } from 'react';
import PublicSectionHeading from '@/components/public-section-heading';
import RegionalHeroArtwork from '@/components/regional-hero-artwork';
import StoryCard from '@/components/story-card';
import TerritoryFinder from '@/components/territory-finder';
import { register } from '@/routes';
import { index as stories, section } from '@/routes/stories';
import { index } from '@/routes/territory';
import type { PublicStory } from '@/types/editorial';

type Region = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    municipalities_count: number;
    publications_count: number;
};

export default function Welcome({
    stateCount,
    municipalityCount,
    featured,
    recent,
    causos,
    culture,
    regions,
    canonical,
    ogImage,
}: {
    stateCount: number;
    municipalityCount: number;
    featured: PublicStory[];
    recent: PublicStory[];
    causos: PublicStory[];
    culture: PublicStory[];
    regions: Region[];
    canonical: string;
    ogImage: { src: string; width: number; height: number } | null;
}) {
    const hero = featured[0];

    return (
        <div className="space-y-12 sm:space-y-20">
            <Head title="Nossas raízes seguem conosco">
                <meta
                    name="description"
                    content="Histórias, causos, memórias, cultura e lugares que mantêm vivas as raízes de quem partiu e de quem ficou."
                />
                <link rel="canonical" href={canonical} />
                <meta
                    property="og:title"
                    content="Nossas raízes seguem conosco"
                />
                <meta
                    property="og:description"
                    content="Histórias e lugares que mantêm vivas nossas raízes."
                />
                {ogImage && (
                    <meta
                        property="og:image"
                        content={new URL(ogImage.src, canonical).toString()}
                    />
                )}
            </Head>

            <section className="grid items-center gap-6 lg:grid-cols-[.82fr_1.18fr] lg:gap-12">
                <div className="py-1 sm:py-4">
                    <p className="public-eyebrow mb-4">
                        Memória · território · pertencimento
                    </p>
                    <h1 className="max-w-[15ch] font-editorial text-4xl leading-[1.02] tracking-tight text-public-ink sm:max-w-[12ch] sm:text-6xl lg:text-7xl">
                        Há lugares que seguem{' '}
                        <span className="text-public-leather italic">
                            com a gente.
                        </span>
                    </h1>
                    <p className="mt-4 max-w-xl text-base leading-relaxed text-public-muted sm:mt-6 sm:text-xl">
                        Memórias, paisagens, costumes e pessoas que continuam
                        fazendo parte de quem somos — de onde estivermos.
                    </p>
                    <div className="mt-6 max-w-xl sm:mt-8">
                        <TerritoryFinder compact />
                    </div>
                </div>
                {hero ? (
                    <div className="home-feature">
                        <StoryCard story={hero} prominent />
                    </div>
                ) : (
                    <RegionalHeroArtwork />
                )}
            </section>

            {featured.length > 1 && (
                <section>
                    <PublicSectionHeading
                        eyebrow="Escolhas da curadoria"
                        title="Outras histórias em destaque"
                        href={stories().url}
                    />
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {featured.slice(1).map((story) => (
                            <StoryCard key={story.slug} story={story} />
                        ))}
                    </div>
                </section>
            )}

            <section className="public-section">
                <PublicSectionHeading
                    eyebrow="Um acervo feito de muitas vozes"
                    title="Por onde sua memória quer começar?"
                />
                <div className="grid gap-3 sm:grid-cols-3 sm:gap-4">
                    <CollectionPathway
                        number="01"
                        icon={<BookOpen aria-hidden="true" />}
                        title="Vidas e travessias"
                        description="Lembranças pessoais, migrações e caminhos entre lugares."
                        href={section('historias').url}
                    />
                    <CollectionPathway
                        number="02"
                        icon={<MessageCircleMore aria-hidden="true" />}
                        title="Causos"
                        description="Histórias contadas com humor, detalhe e jeito próprio."
                        href={section('causos').url}
                    />
                    <CollectionPathway
                        number="03"
                        icon={<Sprout aria-hidden="true" />}
                        title="Saberes e tradições"
                        description="Práticas culturais, costumes e memória coletiva."
                        href={section('cultura-e-tradicoes').url}
                    />
                </div>
            </section>

            {recent.length > 0 && (
                <section className="public-section">
                    <PublicSectionHeading
                        eyebrow="Narrativas da comunidade"
                        title="Vidas, lembranças e travessias"
                        href={stories().url}
                    />
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {recent.map((story) => (
                            <StoryCard key={story.slug} story={story} />
                        ))}
                    </div>
                </section>
            )}

            {causos.length > 0 && (
                <EditorialSection
                    eyebrow="Humor e oralidade"
                    title="Causos que passam de boca em boca"
                    stories={causos}
                    href={section('causos').url}
                />
            )}
            {culture.length > 0 && (
                <EditorialSection
                    eyebrow="Cultura vivida"
                    title="Saberes, costumes e tradições"
                    stories={culture}
                    href={section('cultura-e-tradicoes').url}
                />
            )}

            <section className="public-section">
                <PublicSectionHeading
                    eyebrow="Caminhos de descoberta"
                    title="A memória também mora no território."
                    href={index().url}
                    label="Explorar territórios"
                />
                {regions.length ? (
                    <div className="grid gap-px overflow-hidden rounded-2xl border border-public-rule bg-public-rule md:grid-cols-2 xl:grid-cols-3">
                        {regions.map((region, position) => (
                            <Link
                                key={region.id}
                                href={`/regioes/${region.slug}`}
                                className="public-focus group flex min-h-48 flex-col items-start bg-public-paper p-6 transition-colors hover:bg-public-surface sm:p-8"
                            >
                                <span className="flex items-center gap-2 text-xs font-semibold tracking-[0.15em] text-public-leather uppercase">
                                    <MapPin
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    {position === 0
                                        ? 'Comece por aqui'
                                        : 'Região em evidência'}
                                </span>
                                <h3 className="mt-5 font-editorial text-3xl leading-tight tracking-tight text-public-ink">
                                    {region.name}
                                </h3>
                                <p className="mt-3 flex-1 leading-relaxed text-public-muted">
                                    {region.description ??
                                        `${region.municipalities_count} municípios · ${region.publications_count} conteúdos relacionados`}
                                </p>
                                <span className="mt-5 inline-flex items-center gap-2 font-semibold text-public-leather">
                                    Conhecer a região
                                    <ArrowRight
                                        className="size-4 transition-transform group-hover:translate-x-1"
                                        aria-hidden="true"
                                    />
                                </span>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="territory-invitation grid gap-6 rounded-2xl bg-public-mate p-6 text-public-paper sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div>
                            <p className="public-eyebrow !text-[#e2aa88]">
                                Um país de muitos lugares
                            </p>
                            <h3 className="mt-2 max-w-[20ch] font-editorial text-3xl leading-tight sm:text-4xl">
                                Comece pelo município que vive na sua lembrança.
                            </h3>
                            <p className="mt-3 max-w-2xl leading-relaxed text-public-paper/80">
                                O catálogo já reúne {stateCount} estados e{' '}
                                {municipalityCount.toLocaleString('pt-BR')}{' '}
                                municípios para explorar.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <TerritoryFinder compact />
                            <Link
                                href={index().url}
                                className="public-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-public-paper/50 px-5 py-2.5 font-semibold text-public-paper hover:bg-public-paper/10"
                            >
                                Ver estados
                                <ArrowRight
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Link>
                        </div>
                    </div>
                )}
            </section>

            <section className="community-invitation overflow-hidden rounded-3xl border border-public-rule bg-public-surface px-6 py-7 sm:px-10 sm:py-9 lg:flex lg:items-end lg:justify-between lg:gap-10">
                <div>
                    <p className="public-eyebrow">Uma comunidade de memória</p>
                    <h2 className="mt-3 max-w-[18ch] font-editorial text-3xl leading-tight tracking-tight text-public-ink sm:text-4xl">
                        Toda lembrança merece ser escutada com cuidado.
                    </h2>
                    <p className="mt-3 max-w-2xl leading-relaxed text-public-muted">
                        Crie sua conta para acompanhar o portal e fazer parte da
                        comunidade. As publicações passam por curadoria antes de
                        aparecer no acervo.
                    </p>
                </div>
                <Link
                    href={register()}
                    className="public-primary-link mt-6 w-full sm:w-auto lg:mt-0"
                >
                    Fazer parte
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            </section>
        </div>
    );
}

function EditorialSection({
    eyebrow,
    title,
    stories: items,
    href,
}: {
    eyebrow: string;
    title: string;
    stories: PublicStory[];
    href: string;
}) {
    return (
        <section className="public-section">
            <PublicSectionHeading eyebrow={eyebrow} title={title} href={href} />
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                {items.map((story) => (
                    <StoryCard key={story.slug} story={story} />
                ))}
            </div>
        </section>
    );
}

function CollectionPathway({
    number,
    icon,
    title,
    description,
    href,
}: {
    number: string;
    icon: ReactNode;
    title: string;
    description: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="public-focus group flex min-h-48 flex-col rounded-2xl border border-public-rule bg-public-surface p-5 transition-colors hover:border-public-leather sm:p-6"
        >
            <span className="flex items-center justify-between text-sm text-public-leather">
                <span className="grid size-10 place-items-center rounded-full bg-public-paper">
                    {icon}
                </span>
                <span className="font-editorial text-lg">{number}</span>
            </span>
            <span className="mt-5 font-editorial text-2xl leading-tight text-public-ink">
                {title}
            </span>
            <span className="mt-2 flex-1 text-sm leading-relaxed text-public-muted">
                {description}
            </span>
            <span className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-public-leather">
                Explorar coleção
                <ArrowRight
                    className="size-4 transition-transform group-hover:translate-x-1"
                    aria-hidden="true"
                />
            </span>
        </Link>
    );
}
