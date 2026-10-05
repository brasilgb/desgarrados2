import { Head, Link } from '@inertiajs/react';
import { state as showState } from '@/routes/territory';
import type { Municipality, Region, TerritoryState } from '@/types/territory';

export default function MunicipalityPage({
    state,
    municipality,
    regions,
}: {
    state: TerritoryState;
    municipality: Municipality;
    regions: Region[];
}) {
    return (
        <>
            <Head title={`${municipality.name}, ${state.abbreviation}`} />
            <Link
                href={showState(state.slug)}
                className="text-sm underline underline-offset-4"
            >
                ← {state.name}
            </Link>
            <h1 className="mt-6 font-serif text-4xl sm:text-6xl">
                {municipality.name}
            </h1>
            <p className="mt-4 text-lg opacity-80">
                {state.name} · {state.abbreviation}
            </p>
            <p className="mt-2 text-sm opacity-70">
                Código IBGE {municipality.ibge_code}
            </p>
            <section className="mt-10 max-w-3xl rounded-2xl border border-current/20 p-6 sm:p-8">
                <h2 className="font-serif text-2xl">As regiões deste lugar</h2>
                {regions.length ? (
                    <ul className="mt-5 flex flex-col gap-5">
                        {regions.map((region) => (
                            <li key={region.id}>
                                <h3 className="font-medium">{region.name}</h3>
                                <p className="text-xs uppercase opacity-70">
                                    Região{' '}
                                    {region.kind === 'cultural'
                                        ? 'cultural'
                                        : 'geográfica'}
                                </p>
                                {region.description && (
                                    <p className="mt-2 text-sm leading-relaxed">
                                        {region.description}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-4 leading-relaxed opacity-80">
                        As relações culturais e geográficas deste município
                        ainda estão em curadoria. Um lugar pode pertencer a mais
                        de uma região.
                    </p>
                )}
            </section>
        </>
    );
}
