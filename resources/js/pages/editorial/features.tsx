import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Errors, actionClass, fieldClass } from '@/components/editorial-fields';
import TerritoryPagination from '@/components/editorial-pagination';
import type { Option, Page } from '@/types/editorial';

type Feature = {
    id: number;
    publication_id: number;
    region_id: number | null;
    position: number;
    starts_at: string;
    ends_at: string | null;
    publication: { published_revision: { title: string } };
    region: Option | null;
    curator: Option | null;
};
const localValue = (value?: string | null) => (value ? value.slice(0, 16) : '');
export default function Features({
    features,
    publications,
    regions,
}: {
    features: Page<Feature>;
    publications: Option[];
    regions: Option[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const form = useForm({
        publication_id: '',
        region_id: '',
        position: '1',
        starts_at: localValue(new Date().toISOString()),
        ends_at: '',
    });
    const reset = () => {
        setEditing(null);
        form.setData({
            publication_id: '',
            region_id: '',
            position: '1',
            starts_at: localValue(new Date().toISOString()),
            ends_at: '',
        });
    };
    return (
        <section className="mx-auto w-full max-w-5xl space-y-6 p-6">
            <Head title="Destaques editoriais">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <Link href="/administracao/editorial">← Redação</Link>
            <h1 className="text-3xl font-semibold">Destaques editoriais</h1>
            <p className="text-muted-foreground">
                A posição precisa estar livre no período. Sem região, o destaque
                pertence à home geral.
            </p>
            <form
                className="grid gap-4 rounded-xl border p-5 md:grid-cols-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(
                        `/administracao/editorial/destaques${editing ? `/${editing}` : ''}`,
                        { onSuccess: reset },
                    );
                }}
            >
                <Errors errors={form.errors} />
                <label>
                    Publicação
                    <select
                        required
                        className={fieldClass}
                        value={form.data.publication_id}
                        onChange={(e) =>
                            form.setData('publication_id', e.target.value)
                        }
                    >
                        <option value="">Selecione</option>
                        {publications.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Região (opcional)
                    <select
                        className={fieldClass}
                        value={form.data.region_id}
                        onChange={(e) =>
                            form.setData('region_id', e.target.value)
                        }
                    >
                        <option value="">Home geral</option>
                        {regions.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Posição
                    <input
                        className={fieldClass}
                        type="number"
                        min="1"
                        max="12"
                        required
                        value={form.data.position}
                        onChange={(e) =>
                            form.setData('position', e.target.value)
                        }
                    />
                </label>
                <label>
                    Início
                    <input
                        className={fieldClass}
                        type="datetime-local"
                        required
                        value={form.data.starts_at}
                        onChange={(e) =>
                            form.setData('starts_at', e.target.value)
                        }
                    />
                </label>
                <label>
                    Fim (opcional)
                    <input
                        className={fieldClass}
                        type="datetime-local"
                        value={form.data.ends_at}
                        onChange={(e) =>
                            form.setData('ends_at', e.target.value)
                        }
                    />
                </label>
                <div className="flex items-end gap-2">
                    <button className={actionClass} disabled={form.processing}>
                        {editing ? 'Salvar alteração' : 'Agendar destaque'}
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
                {features.data.map((feature) => (
                    <li
                        key={feature.id}
                        className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                        <div>
                            <p className="font-semibold">
                                Posição {feature.position} ·{' '}
                                {feature.publication.published_revision.title}
                            </p>
                            <p className="text-sm opacity-70">
                                {feature.region?.name ?? 'Home geral'} ·{' '}
                                {new Date(feature.starts_at).toLocaleString(
                                    'pt-BR',
                                )}{' '}
                                até{' '}
                                {feature.ends_at
                                    ? new Date(feature.ends_at).toLocaleString(
                                          'pt-BR',
                                      )
                                    : 'sem término'}{' '}
                                ·{' '}
                                {feature.curator?.name ??
                                    'Responsável removido'}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <button
                                className={actionClass}
                                onClick={() => {
                                    setEditing(feature.id);
                                    form.setData({
                                        publication_id: String(
                                            feature.publication_id,
                                        ),
                                        region_id: feature.region_id
                                            ? String(feature.region_id)
                                            : '',
                                        position: String(feature.position),
                                        starts_at: localValue(
                                            feature.starts_at,
                                        ),
                                        ends_at: localValue(feature.ends_at),
                                    });
                                }}
                            >
                                Editar
                            </button>
                            <button
                                className={actionClass}
                                onClick={() =>
                                    window.confirm('Remover este destaque?') &&
                                    router.delete(
                                        `/administracao/editorial/destaques/${feature.id}`,
                                    )
                                }
                            >
                                Remover
                            </button>
                        </div>
                    </li>
                ))}
            </ul>
            <TerritoryPagination links={features.links} />
        </section>
    );
}
