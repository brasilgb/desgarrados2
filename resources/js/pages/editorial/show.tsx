import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    ContentFields,
    Errors,
    Lookup,
    actionClass,
    fieldClass,
} from '@/components/editorial-fields';
import EditorialMedia from '@/components/editorial-media';
import TerritoryPagination from '@/components/editorial-pagination';
import {
    index,
    metadata,
    revision as saveRevision,
    show,
    transition,
} from '@/routes/editorial';
import type {
    Content,
    MediaAsset,
    Option,
    Page,
    RevisionMediaItem,
} from '@/types/editorial';
import { labels } from '@/types/editorial';
type Revision = Content & {
    id: number;
    version: number;
    status: string;
    review_note: string | null;
};
type Publication = {
    id: number;
    slug: string;
    type: string;
    status: string;
    published_revision_id: number | null;
    scheduled_for: string | null;
    municipality: Option | null;
    category: Option | null;
    tags: Option[];
    regions: Option[];
};
export default function Show({
    publication: p,
    revision: r,
    revisions,
    permissions: can,
    media,
    library,
    rightsTypes,
    uploadLimitMb,
}: {
    publication: Publication;
    revision: Revision;
    revisions: Page<Revision>;
    permissions: {
        manage: boolean;
        review: boolean;
        submit: boolean;
        update: boolean;
        editMedia: boolean;
    };
    media: RevisionMediaItem[];
    library: MediaAsset[];
    rightsTypes: string[];
    uploadLimitMb: number;
}) {
    const form = useForm<Content>({
        title: r.title,
        summary: r.summary ?? '',
        body: r.body,
        public_byline: r.public_byline ?? '',
        sources: r.sources.map((s) => ({
            title: s.title,
            url: s.url ?? '',
            attribution: s.attribution ?? '',
            accessed_at: s.accessed_at ?? '',
        })),
    });
    const operation = useForm({
        action: '',
        revision_id: r.id,
        note: '',
        scheduled_for: '',
    });
    const meta = useForm({
        slug: p.slug,
        municipality_id: p.municipality?.id ?? (null as number | null),
        category_id: p.category?.id ?? (null as number | null),
        tag_ids: p.tags.map((t) => t.id),
        region_ids: p.regions.map((t) => t.id),
    });
    const [selected, setSelected] = useState({
        municipalities: p.municipality ? [p.municipality] : [],
        categories: p.category ? [p.category] : [],
        tags: p.tags,
        regions: p.regions,
    });
    const [editing, setEditing] = useState(false);
    function act(action: string) {
        operation.transform((data) => ({
            ...data,
            action,
            scheduled_for: data.scheduled_for
                ? new Date(data.scheduled_for).toISOString()
                : '',
        }));
        operation.post(transition(p.id).url, { preserveScroll: true });
    }
    return (
        <section className="mx-auto w-full max-w-5xl space-y-6 p-6">
            <Head title={r.title}>
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <Link href={index()}>← Redação</Link>
            <header className="space-y-3">
                <p>
                    {labels[p.type]} · {labels[p.status]} · revisão {r.version}{' '}
                    ({labels[r.status]})
                </p>
                <h1 className="text-3xl font-semibold">{r.title}</h1>
                {p.published_revision_id && (
                    <p className="rounded-lg bg-muted p-3">
                        A edição pública continua usando a revisão aprovada #
                        {p.published_revision_id} até nova publicação.
                    </p>
                )}
                {p.scheduled_for && (
                    <p role="status">
                        Agendada:{' '}
                        {new Date(p.scheduled_for).toLocaleString('pt-BR', {
                            timeZone: 'America/Sao_Paulo',
                        })}
                    </p>
                )}
            </header>
            <nav
                className="flex flex-wrap gap-2"
                aria-label="Histórico de versões"
            >
                {revisions.data.map((item) => (
                    <Link
                        key={item.id}
                        href={show(p.id, { query: { revision: item.id } })}
                        className={actionClass}
                    >
                        v{item.version} · {labels[item.status]}
                    </Link>
                ))}
            </nav>
            <TerritoryPagination links={revisions.links} />
            <Errors errors={operation.errors} />
            <div className="space-y-4 rounded-xl border p-5">
                <h2 className="text-xl font-semibold">Fluxo editorial</h2>
                {r.review_note && (
                    <p className="whitespace-pre-wrap">
                        Nota da revisão: {r.review_note}
                    </p>
                )}
                <div className="flex flex-wrap gap-3">
                    {can.submit && (
                        <button
                            className={actionClass}
                            disabled={operation.processing}
                            onClick={() => act('submit')}
                        >
                            Enviar para revisão
                        </button>
                    )}
                    {can.review && (
                        <>
                            <label className="w-full space-y-2">
                                <span>
                                    Nota de revisão (obrigatória para rejeitar)
                                </span>
                                <textarea
                                    className={fieldClass}
                                    value={operation.data.note}
                                    onChange={(e) =>
                                        operation.setData(
                                            'note',
                                            e.target.value,
                                        )
                                    }
                                />
                            </label>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => act('approve')}
                            >
                                Aprovar revisão
                            </button>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => act('reject')}
                            >
                                Rejeitar revisão
                            </button>
                        </>
                    )}
                    {can.manage && r.status === 'approved' && (
                        <>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => act('publish')}
                            >
                                Publicar esta revisão
                            </button>
                            <label className="space-y-2">
                                <span className="block">
                                    Agendar (seu horário local)
                                </span>
                                <input
                                    className={fieldClass}
                                    type="datetime-local"
                                    value={operation.data.scheduled_for}
                                    onChange={(e) =>
                                        operation.setData(
                                            'scheduled_for',
                                            e.target.value,
                                        )
                                    }
                                />
                            </label>
                            <button
                                className={actionClass}
                                disabled={
                                    operation.processing ||
                                    !operation.data.scheduled_for
                                }
                                onClick={() => act('schedule')}
                            >
                                Agendar revisão
                            </button>
                        </>
                    )}
                    {can.manage && (
                        <>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => act('hide')}
                            >
                                Ocultar
                            </button>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => act('archive')}
                            >
                                Arquivar
                            </button>
                            <button
                                className={actionClass}
                                disabled={operation.processing}
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            'Excluir esta publicação? O histórico será preservado.',
                                        )
                                    )
                                        act('delete');
                                }}
                            >
                                Excluir publicação
                            </button>
                        </>
                    )}
                </div>
                {r.status === 'in_review' && (
                    <p role="status">
                        Esta versão está congelada para análise. Uma alteração
                        de texto cria uma nova revisão.
                    </p>
                )}
                {!can.review && r.status === 'in_review' && (
                    <p className="text-sm text-muted-foreground">
                        A análise exige outro editor autorizado.
                    </p>
                )}
            </div>
            {editing ? (
                <form
                    key={r.id}
                    className="space-y-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        // The new revision starts with a copy of this revision's images.
                        form.transform((data) => ({
                            ...data,
                            base_revision_id: r.id,
                        }));
                        form.post(saveRevision(p.id).url, {
                            onSuccess: () => setEditing(false),
                        });
                    }}
                >
                    <Errors errors={form.errors} />
                    <ContentFields
                        data={form.data}
                        change={form.setData}
                        disabled={form.processing}
                    />
                    <button className={actionClass} disabled={form.processing}>
                        Salvar como nova revisão
                    </button>
                </form>
            ) : (
                <article className="space-y-5 rounded-xl border p-6">
                    <p className="text-xl text-muted-foreground">{r.summary}</p>
                    <div className="leading-8 whitespace-pre-wrap">
                        {r.body}
                    </div>
                    <p>{r.public_byline}</p>
                    {can.update && (
                        <button
                            className={actionClass}
                            onClick={() => {
                                form.setData({
                                    title: r.title,
                                    summary: r.summary ?? '',
                                    body: r.body,
                                    public_byline: r.public_byline ?? '',
                                    sources: r.sources.map((s) => ({
                                        title: s.title,
                                        url: s.url ?? '',
                                        attribution: s.attribution ?? '',
                                        accessed_at: s.accessed_at ?? '',
                                    })),
                                });
                                setEditing(true);
                            }}
                        >
                            Editar em nova revisão
                        </button>
                    )}
                </article>
            )}
            <EditorialMedia
                publicationId={p.id}
                revisionId={r.id}
                items={media}
                library={library}
                rightsTypes={rightsTypes}
                canEdit={can.editMedia}
                uploadLimitMb={uploadLimitMb}
            />
            {can.manage && (
                <form
                    className="space-y-5 rounded-xl border p-6"
                    onSubmit={(e) => {
                        e.preventDefault();
                        meta.patch(metadata(p.id).url, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <h2 className="text-xl font-semibold">
                        Território e organização editorial
                    </h2>
                    <Errors errors={meta.errors} />
                    <label className="block space-y-2">
                        <span>
                            Slug atual (alterações preservam o endereço antigo)
                        </span>
                        <input
                            className={fieldClass}
                            value={meta.data.slug}
                            onChange={(e) =>
                                meta.setData('slug', e.target.value)
                            }
                        />
                    </label>
                    <div className="grid gap-4 md:grid-cols-2">
                        {(
                            [
                                'municipalities',
                                'categories',
                                'tags',
                                'regions',
                            ] as const
                        ).map((kind) => (
                            <Lookup
                                key={kind}
                                kind={kind}
                                label={
                                    {
                                        municipalities: 'Município principal',
                                        categories: 'Categoria principal',
                                        tags: 'Tags',
                                        regions: 'Regiões',
                                    }[kind]
                                }
                                selected={selected[kind]}
                                multiple={kind === 'tags' || kind === 'regions'}
                                onChange={(items) => {
                                    setSelected((old) => ({
                                        ...old,
                                        [kind]: items,
                                    }));
                                    if (kind === 'municipalities')
                                        meta.setData(
                                            'municipality_id',
                                            items[0]?.id ?? null,
                                        );
                                    else if (kind === 'categories')
                                        meta.setData(
                                            'category_id',
                                            items[0]?.id ?? null,
                                        );
                                    else
                                        meta.setData(
                                            kind === 'tags'
                                                ? 'tag_ids'
                                                : 'region_ids',
                                            items.map((item) => item.id),
                                        );
                                }}
                            />
                        ))}
                    </div>
                    <button className={actionClass} disabled={meta.processing}>
                        Salvar organização
                    </button>
                </form>
            )}
        </section>
    );
}
