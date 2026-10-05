import { Head, Link } from '@inertiajs/react';
import { state } from '@/routes/territory';
import type { TerritoryState } from '@/types/territory';

export default function States({ states }: { states: TerritoryState[] }) {
    return (
        <>
            <Head title="Explorar o Brasil" />
            <p className="text-sm tracking-widest uppercase">
                Território e pertencimento
            </p>
            <h1 className="mt-3 font-serif text-4xl sm:text-5xl">
                Cada lugar guarda uma história.
            </h1>
            <p className="mt-5 max-w-2xl leading-relaxed opacity-80">
                Comece pelo seu estado, pelo que você deixou ou pelo que sempre
                quis conhecer.
            </p>
            {states.length === 0 && (
                <p className="mt-10" role="status">
                    O catálogo territorial está sendo preparado. Volte em breve.
                </p>
            )}
            <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {states.map((item) => (
                    <Link
                        key={item.id}
                        href={state(item.slug)}
                        className="group flex flex-col gap-3 rounded-2xl border border-current/20 bg-white/40 p-6 transition-colors hover:bg-white/80 focus-visible:outline-2 dark:bg-white/5 dark:hover:bg-white/10"
                    >
                        <span className="text-xs tracking-widest uppercase">
                            {item.abbreviation === 'RS'
                                ? 'Nosso ponto de partida'
                                : item.abbreviation}
                        </span>
                        <h2 className="font-serif text-2xl">{item.name}</h2>
                        <span className="text-sm opacity-75">
                            {item.municipalities_count} municípios · Explorar →
                        </span>
                    </Link>
                ))}
            </div>
        </>
    );
}
