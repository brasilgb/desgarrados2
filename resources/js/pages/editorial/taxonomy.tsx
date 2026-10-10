import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Errors, actionClass, fieldClass } from '@/components/editorial-fields';
import TerritoryPagination from '@/components/editorial-pagination';
import { index, taxonomy } from '@/routes/editorial';
import { save, deleteMethod as destroy } from '@/routes/editorial/taxonomy';
import type { Option, Page } from '@/types/editorial';
export default function Taxonomy({
    kind,
    items,
    q,
}: {
    kind: string;
    items: Page<Option & { slug: string }>;
    q: string;
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const form = useForm({ name: '', slug: '' });
    const filter = useForm({ q });
    return (
        <section className="mx-auto w-full max-w-4xl space-y-6 p-6">
            <Head title={kind === 'categories' ? 'Categorias' : 'Tags'}>
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <Link href={index()}>← Redação</Link>
            <h1 className="text-3xl font-semibold">
                {kind === 'categories' ? 'Categorias' : 'Tags'}
            </h1>
            <form
                className="flex flex-wrap gap-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    filter.get(taxonomy(kind).url);
                }}
            >
                <label className="grow">
                    <span className="sr-only">Buscar por nome</span>
                    <input
                        className={fieldClass}
                        value={filter.data.q}
                        onChange={(e) => filter.setData('q', e.target.value)}
                        placeholder="Buscar por nome"
                    />
                </label>
                <button className={actionClass}>Buscar</button>
            </form>
            <form
                className="space-y-4 rounded-xl border p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(save({ kind, id: editing ?? undefined }).url, {
                        onSuccess: () => {
                            form.reset();
                            setEditing(null);
                        },
                    });
                }}
            >
                <h2 className="text-xl font-semibold">
                    {editing ? 'Editar' : 'Criar'}
                </h2>
                <Errors errors={form.errors} />
                <label className="block space-y-2">
                    <span>Nome</span>
                    <input
                        className={fieldClass}
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </label>
                <label className="block space-y-2">
                    <span>Slug (opcional)</span>
                    <input
                        className={fieldClass}
                        value={form.data.slug}
                        onChange={(e) => form.setData('slug', e.target.value)}
                    />
                </label>
                <button className={actionClass} disabled={form.processing}>
                    Salvar
                </button>
                {editing && (
                    <button
                        type="button"
                        className={actionClass}
                        onClick={() => {
                            form.reset();
                            setEditing(null);
                        }}
                    >
                        Cancelar
                    </button>
                )}
            </form>
            {items.data.length === 0 && <p>Nenhum registro encontrado.</p>}
            <ul className="divide-y rounded-xl border">
                {items.data.map((item) => (
                    <li
                        key={item.id}
                        className="flex flex-wrap items-center justify-between gap-3 p-4"
                    >
                        <div>
                            <p className="font-medium">{item.name}</p>
                            <p className="text-sm text-muted-foreground">
                                {item.slug}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <button
                                className={actionClass}
                                onClick={() => {
                                    setEditing(item.id);
                                    form.setData({
                                        name: item.name,
                                        slug: item.slug,
                                    });
                                }}
                            >
                                Editar
                            </button>
                            <button
                                className={actionClass}
                                onClick={() => {
                                    if (
                                        window.confirm('Excluir este registro?')
                                    )
                                        router.delete(
                                            destroy({ kind, id: item.id }).url,
                                        );
                                }}
                            >
                                Excluir
                            </button>
                        </div>
                    </li>
                ))}
            </ul>
            <TerritoryPagination links={items.links} />
        </section>
    );
}
