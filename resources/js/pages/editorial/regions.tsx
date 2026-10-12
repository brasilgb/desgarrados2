import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Errors, actionClass, fieldClass } from '@/components/editorial-fields';
import TerritoryPagination from '@/components/editorial-pagination';
import type { Option, Page } from '@/types/editorial';

type Region = Option & {
    slug: string;
    kind: 'cultural' | 'geographic';
    description: string | null;
    active: boolean;
    municipalities: Option[];
    municipalities_count: number;
    publications_count: number;
};
type RegionForm = {
    name: string;
    slug: string;
    kind: 'cultural' | 'geographic';
    description: string;
    active: boolean;
    municipality_ids: number[];
};
const empty: RegionForm = {
    name: '',
    slug: '',
    kind: 'cultural',
    description: '',
    active: true,
    municipality_ids: [],
};

export default function Regions({
    regions,
    q,
}: {
    regions: Page<Region>;
    q: string;
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<Option[]>([]);
    const form = useForm<RegionForm>(empty);
    const filter = useForm({ q });
    const selected = new Map(
        [
            ...regions.data.flatMap((region) => region.municipalities),
            ...results,
        ].map((item) => [item.id, item]),
    );
    const reset = () => {
        setEditing(null);
        form.setData(empty);
    };
    return (
        <section className="mx-auto w-full max-w-5xl space-y-6 p-6">
            <Head title="Regiões editoriais">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <Link href="/administracao/editorial">← Redação</Link>
            <h1 className="text-3xl font-semibold">Regiões editoriais</h1>
            <p className="text-muted-foreground">
                A associação é curatorial e não altera os dados oficiais
                importados do IBGE.
            </p>
            <form
                className="flex gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    filter.get('/administracao/editorial/regioes');
                }}
            >
                <input
                    className={fieldClass}
                    value={filter.data.q}
                    onChange={(event) =>
                        filter.setData('q', event.target.value)
                    }
                    placeholder="Buscar região"
                />
                <button className={actionClass}>Buscar</button>
            </form>
            <form
                className="space-y-4 rounded-xl border p-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(
                        `/administracao/editorial/regioes${editing ? `/${editing}` : ''}`,
                        { onSuccess: reset },
                    );
                }}
            >
                <h2 className="text-xl font-semibold">
                    {editing ? 'Editar região' : 'Cadastrar região'}
                </h2>
                <Errors errors={form.errors} />
                <div className="grid gap-4 md:grid-cols-2">
                    <label>
                        Nome
                        <input
                            className={fieldClass}
                            required
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                        />
                    </label>
                    <label>
                        Slug (opcional)
                        <input
                            className={fieldClass}
                            value={form.data.slug}
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                        />
                    </label>
                    <label>
                        Classificação
                        <select
                            className={fieldClass}
                            value={form.data.kind}
                            onChange={(e) =>
                                form.setData(
                                    'kind',
                                    e.target.value as 'cultural' | 'geographic',
                                )
                            }
                        >
                            <option value="cultural">Cultural</option>
                            <option value="geographic">Geográfica</option>
                        </select>
                    </label>
                    <label className="flex items-center gap-2 pt-7">
                        <input
                            type="checkbox"
                            checked={form.data.active}
                            onChange={(e) =>
                                form.setData('active', e.target.checked)
                            }
                        />{' '}
                        Região ativa
                    </label>
                </div>
                <label className="block">
                    Descrição
                    <textarea
                        className={fieldClass}
                        rows={4}
                        value={form.data.description}
                        onChange={(e) =>
                            form.setData('description', e.target.value)
                        }
                    />
                </label>
                <fieldset className="space-y-3">
                    <legend className="font-medium">
                        Municípios relacionados
                    </legend>
                    <div className="flex gap-2">
                        <input
                            className={fieldClass}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Nome do município"
                        />
                        <button
                            type="button"
                            className={actionClass}
                            onClick={async () => {
                                const response = await fetch(
                                    `/administracao/editorial/busca/municipalities?q=${encodeURIComponent(search)}`,
                                    { headers: { Accept: 'application/json' } },
                                );
                                if (response.ok)
                                    setResults(await response.json());
                            }}
                        >
                            Localizar
                        </button>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {form.data.municipality_ids.map((id) => (
                            <button
                                type="button"
                                key={id}
                                className="rounded-full border px-3 py-1"
                                onClick={() =>
                                    form.setData(
                                        'municipality_ids',
                                        form.data.municipality_ids.filter(
                                            (value) => value !== id,
                                        ),
                                    )
                                }
                            >
                                {selected.get(id)?.name ?? `Município ${id}`} ×
                            </button>
                        ))}
                    </div>
                    <div className="grid gap-2 md:grid-cols-2">
                        {results
                            .filter(
                                (item) =>
                                    !form.data.municipality_ids.includes(
                                        item.id,
                                    ),
                            )
                            .map((item) => (
                                <button
                                    type="button"
                                    key={item.id}
                                    className="rounded border p-2 text-left"
                                    onClick={() =>
                                        form.setData('municipality_ids', [
                                            ...form.data.municipality_ids,
                                            item.id,
                                        ])
                                    }
                                >
                                    + {item.name}
                                </button>
                            ))}
                    </div>
                </fieldset>
                <div className="flex gap-2">
                    <button className={actionClass} disabled={form.processing}>
                        Salvar
                    </button>
                    {editing && (
                        <button
                            type="button"
                            className={actionClass}
                            onClick={reset}
                        >
                            Cancelar
                        </button>
                    )}
                </div>
            </form>
            <ul className="divide-y rounded-xl border">
                {regions.data.map((region) => (
                    <li
                        key={region.id}
                        className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                        <div>
                            <p className="font-semibold">
                                {region.name}{' '}
                                {!region.active && (
                                    <span className="text-sm opacity-60">
                                        (inativa)
                                    </span>
                                )}
                            </p>
                            <p className="text-sm opacity-70">
                                {region.kind === 'cultural'
                                    ? 'Cultural'
                                    : 'Geográfica'}{' '}
                                · {region.municipalities_count} municípios ·{' '}
                                {region.publications_count} publicações
                            </p>
                        </div>
                        <button
                            className={actionClass}
                            onClick={() => {
                                setEditing(region.id);
                                form.setData({
                                    name: region.name,
                                    slug: region.slug,
                                    kind: region.kind,
                                    description: region.description ?? '',
                                    active: region.active,
                                    municipality_ids: region.municipalities.map(
                                        (item) => item.id,
                                    ),
                                });
                            }}
                        >
                            Editar
                        </button>
                    </li>
                ))}
            </ul>
            <TerritoryPagination links={regions.links} />
        </section>
    );
}
