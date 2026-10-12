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
    author: Option | null;
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
    rightsEvidence,
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
    rightsEvidence: {
        id: number;
        name: string;
        mime: string;
        size: number;
        sha256: string;
        status: string;
        created_at: string | null;
    }[];
    rightsTypes: string[];
    uploadLimitMb: number;
}) {
    const form = useForm<Content>({
        title: r.title,
        summary: r.summary ?? '',
        body: r.body,
        public_byline: r.public_byline ?? '',
        rights_status: r.rights_status,
        rights_author: r.rights_author ?? '',
        rights_holder: r.rights_holder ?? '',
        rights_basis: r.rights_basis ?? '',
        rights_reference: r.rights_reference ?? '',
        rights_restrictions: r.rights_restrictions ?? '',
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
    const evidenceForm = useForm({ file: null as File | null });
    const revocation = useForm({ reason: '' });
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
            <a
                className={actionClass}
                href={`/administracao/editorial/publicacoes/${p.id}/revisoes/${r.id}/previa`}
            >
                Pré-visualizar como visitante
            </a>
            <section
                aria-labelledby="publication-context-heading"
                className="space-y-3 rounded-xl border p-5"
            >
                <h2
                    id="publication-context-heading"
                    className="text-lg font-semibold"
                >
                    Identificação e curadoria
                </h2>
                <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Responsável pelo cadastro
                        </dt>
                        <dd>{p.author?.name ?? 'Não informado'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Assinatura pública
                        </dt>
                        <dd>{r.public_byline || 'Não informada'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Direitos do texto
                        </dt>
                        <dd>
                            {
                                {
                                    original: `Original · ${r.rights_author || 'autor não informado'}`,
                                    authorized: `Autorizado · ${r.rights_holder || 'titular não informado'}`,
                                    public_domain: 'Domínio público',
                                    licensed: `Licenciado · ${r.rights_holder || 'titular não informado'}`,
                                    unverified: 'Situação ainda não comprovada',
                                }[r.rights_status]
                            }
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Município principal
                        </dt>
                        <dd>{p.municipality?.name ?? 'Ainda não associado'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Categoria
                        </dt>
                        <dd>{p.category?.name ?? 'Ainda não classificada'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Regiões
                        </dt>
                        <dd>
                            {p.regions.length
                                ? p.regions.map((item) => item.name).join(', ')
                                : 'Ainda não associadas'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Tags</dt>
                        <dd>
                            {p.tags.length
                                ? p.tags.map((item) => item.name).join(', ')
                                : 'Nenhuma tag'}
                        </dd>
                    </div>
                </dl>
            </section>
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
                <p className="text-sm text-muted-foreground" role="status">
                    {r.status === 'draft' &&
                        'Rascunho privado. Confira texto, fontes, créditos e classificação antes de enviar para revisão.'}
                    {r.status === 'in_review' &&
                        'Esta revisão está em análise. O texto fica congelado; editar cria uma nova versão.'}
                    {r.status === 'approved' &&
                        'Revisão aprovada. Um editor pode publicar agora ou programar a publicação.'}
                    {r.status === 'rejected' &&
                        'Revisão devolvida para ajustes. Consulte a nota editorial antes de criar uma nova versão.'}
                </p>
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
                        type={p.type}
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
                                    rights_status: r.rights_status,
                                    rights_author: r.rights_author ?? '',
                                    rights_holder: r.rights_holder ?? '',
                                    rights_basis: r.rights_basis ?? '',
                                    rights_reference: r.rights_reference ?? '',
                                    rights_restrictions:
                                        r.rights_restrictions ?? '',
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
            <section
                className="space-y-4 rounded-xl border p-5"
                aria-labelledby="rights-evidence-heading"
            >
                <h2
                    id="rights-evidence-heading"
                    className="text-xl font-semibold"
                >
                    Comprovantes de direitos autorais
                </h2>
                <p className="text-sm text-muted-foreground">
                    Arquivos privados ligados a esta revisão. Ao anexar nova
                    versão, o histórico anterior é preservado.
                </p>
                {rightsEvidence.length ? (
                    <ul className="space-y-2">
                        {rightsEvidence.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded border p-3 text-sm"
                            >
                                <span>
                                    {item.name} ·{' '}
                                    {item.status === 'current'
                                        ? 'atual'
                                        : 'substituído'}{' '}
                                    · SHA-256 {item.sha256.slice(0, 12)}…
                                </span>
                                <a
                                    className="underline"
                                    href={`/administracao/editorial/comprovantes/${item.id}`}
                                >
                                    Baixar arquivo privado
                                </a>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p>Nenhum comprovante anexado.</p>
                )}
                {can.update && (
                    <form
                        className="flex flex-wrap items-end gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            evidenceForm.post(
                                `/administracao/editorial/revisoes/${r.id}/comprovantes`,
                                {
                                    forceFormData: true,
                                    onSuccess: () => evidenceForm.reset(),
                                },
                            );
                        }}
                    >
                        <label className="flex-1 space-y-2">
                            <span>PDF, JPG ou PNG — até 10 MB</span>
                            <input
                                className={fieldClass}
                                required
                                type="file"
                                accept="application/pdf,image/jpeg,image/png"
                                onChange={(event) =>
                                    evidenceForm.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </label>
                        {evidenceForm.errors.file && (
                            <p role="alert">{evidenceForm.errors.file}</p>
                        )}
                        <button
                            className={actionClass}
                            disabled={
                                evidenceForm.processing ||
                                !evidenceForm.data.file
                            }
                        >
                            {evidenceForm.processing
                                ? 'Enviando…'
                                : 'Anexar comprovante'}
                        </button>
                    </form>
                )}
            </section>
            {can.manage && (p.status === 'published' || p.scheduled_for) && (
                <form
                    className="space-y-3 rounded-xl border border-destructive p-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (
                            window.confirm(
                                'Retirar esta publicação do ar e cancelar agendamentos por revogação ou dúvida de direitos?',
                            )
                        )
                            revocation.post(
                                `/administracao/editorial/publicacoes/${p.id}/revogar-direitos`,
                            );
                    }}
                >
                    <h2 className="font-semibold">Revogação de direitos</h2>
                    <label className="block space-y-2">
                        <span>Motivo e referência da decisão</span>
                        <textarea
                            required
                            maxLength={2000}
                            className={fieldClass}
                            value={revocation.data.reason}
                            onChange={(event) =>
                                revocation.setData('reason', event.target.value)
                            }
                        />
                    </label>
                    {revocation.errors.reason && (
                        <p role="alert">{revocation.errors.reason}</p>
                    )}
                    <button
                        className={actionClass}
                        disabled={revocation.processing}
                    >
                        Retirar do ar e registrar decisão
                    </button>
                </form>
            )}
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
