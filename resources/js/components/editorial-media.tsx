import { router, useForm } from '@inertiajs/react';
import { useId, useState } from 'react';
import { Errors, actionClass, fieldClass } from '@/components/editorial-fields';
import {
    block,
    destroy as destroyAsset,
    original,
    rights as saveRights,
    store as upload,
    unblock,
} from '@/routes/editorial/media';
import {
    destroy as detach,
    move,
    store as attach,
    update as updateUsage,
} from '@/routes/editorial/revision-media';
import type {
    MediaAsset,
    MediaImage,
    RevisionMediaItem,
} from '@/types/editorial';
import { labels } from '@/types/editorial';

type Rights = {
    rights_type: string;
    rights_holder: string;
    license: string;
    rights_notes: string;
};
type UsageText = { alt_text: string; caption: string; credit: string };

function Preview({ image, alt }: { image: MediaImage | null; alt: string }) {
    return image ? (
        <img
            src={image.src}
            width={image.width}
            height={image.height}
            alt={alt}
            loading="lazy"
            decoding="async"
            className="aspect-[4/3] w-full rounded-lg bg-muted object-cover"
        />
    ) : (
        <div className="flex aspect-[4/3] w-full items-center justify-center rounded-lg bg-muted p-3 text-center text-sm text-muted-foreground">
            Prévia indisponível
        </div>
    );
}

function AssetFlags({ asset }: { asset: MediaAsset }) {
    const flags = [
        asset.processing !== 'ready' && labels[asset.processing],
        asset.status === 'blocked' &&
            `Bloqueada${asset.blocked_reason ? `: ${asset.blocked_reason}` : ''}`,
        !asset.has_rights && 'Sem direitos de uso registrados',
    ].filter(Boolean);
    return flags.length ? (
        <ul className="space-y-1 text-sm text-destructive">
            {flags.map((flag) => (
                <li key={String(flag)}>{flag}</li>
            ))}
        </ul>
    ) : (
        <p className="text-sm text-muted-foreground">
            {labels[asset.rights_type ?? '']} · {asset.rights_holder}
        </p>
    );
}

function TextFields({
    data,
    change,
}: {
    data: UsageText;
    change: (key: keyof UsageText, value: string) => void;
}) {
    const id = useId();
    return (
        <>
            {(
                [
                    ['alt_text', 'Texto alternativo (descreva a imagem)', 250],
                    ['caption', 'Legenda (opcional)', 300],
                    ['credit', 'Crédito', 200],
                ] as const
            ).map(([key, label, max]) => (
                <label
                    key={key}
                    className="block space-y-1"
                    htmlFor={`${id}-${key}`}
                >
                    <span className="text-sm">{label}</span>
                    <input
                        id={`${id}-${key}`}
                        className={fieldClass}
                        value={data[key]}
                        maxLength={max}
                        required={key !== 'caption'}
                        onChange={(e) => change(key, e.target.value)}
                    />
                </label>
            ))}
        </>
    );
}

function RightsFields({
    data,
    change,
    types,
}: {
    data: Rights;
    change: (key: keyof Rights, value: string) => void;
    types: string[];
}) {
    const id = useId();
    return (
        <fieldset className="space-y-3">
            <legend className="font-medium">Direitos de uso</legend>
            <label className="block space-y-1" htmlFor={`${id}-type`}>
                <span className="text-sm">Tipo</span>
                <select
                    id={`${id}-type`}
                    className={fieldClass}
                    value={data.rights_type}
                    onChange={(e) => change('rights_type', e.target.value)}
                >
                    <option value="">Ainda não informado</option>
                    {types.map((type) => (
                        <option key={type} value={type}>
                            {labels[type]}
                        </option>
                    ))}
                </select>
            </label>
            {(
                [
                    ['rights_holder', 'Titular dos direitos', 200],
                    ['license', 'Licença (opcional)', 120],
                    ['rights_notes', 'Observações (opcional)', 1000],
                ] as const
            ).map(([key, label, max]) => (
                <label
                    key={key}
                    className="block space-y-1"
                    htmlFor={`${id}-${key}`}
                >
                    <span className="text-sm">{label}</span>
                    <input
                        id={`${id}-${key}`}
                        className={fieldClass}
                        value={data[key]}
                        maxLength={max}
                        required={key === 'rights_holder' && !!data.rights_type}
                        onChange={(e) => change(key, e.target.value)}
                    />
                </label>
            ))}
        </fieldset>
    );
}

function UsageCard({
    item,
    canEdit,
    first,
    last,
    publicationId,
    revisionId,
}: {
    item: RevisionMediaItem;
    canEdit: boolean;
    first: boolean;
    last: boolean;
    publicationId: number;
    revisionId: number;
}) {
    const [editing, setEditing] = useState(false);
    const form = useForm<UsageText>({
        alt_text: item.alt,
        caption: item.caption ?? '',
        credit: item.credit,
    });
    const options = { preserveScroll: true };
    return (
        <li className="space-y-3 rounded-xl border p-4">
            <Preview image={item.image} alt={item.alt} />
            <p className="text-sm font-medium">
                {item.purpose === 'cover' ? 'Capa' : 'Imagem do conteúdo'}
            </p>
            <AssetFlags asset={item.asset} />
            {editing ? (
                <form
                    className="space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.patch(updateUsage(item.id).url, {
                            ...options,
                            onSuccess: () => setEditing(false),
                        });
                    }}
                >
                    <Errors errors={form.errors} />
                    <TextFields
                        data={form.data}
                        change={(key, value) => form.setData(key, value)}
                    />
                    <button className={actionClass} disabled={form.processing}>
                        Salvar textos
                    </button>
                </form>
            ) : (
                <div className="space-y-1 text-sm">
                    <p>
                        <span className="text-muted-foreground">Alt:</span>{' '}
                        {item.alt}
                    </p>
                    {item.caption && <p>{item.caption}</p>}
                    <p className="text-muted-foreground">
                        Crédito: {item.credit}
                    </p>
                </div>
            )}
            {canEdit && (
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        className={actionClass}
                        onClick={() => setEditing(!editing)}
                    >
                        {editing ? 'Cancelar' : 'Editar textos'}
                    </button>
                    {item.purpose === 'content' && (
                        <>
                            <button
                                type="button"
                                className={actionClass}
                                disabled={first}
                                onClick={() =>
                                    router.post(
                                        move(item.id).url,
                                        { direction: 'up' },
                                        options,
                                    )
                                }
                            >
                                Subir
                            </button>
                            <button
                                type="button"
                                className={actionClass}
                                disabled={last}
                                onClick={() =>
                                    router.post(
                                        move(item.id).url,
                                        { direction: 'down' },
                                        options,
                                    )
                                }
                            >
                                Descer
                            </button>
                            <button
                                type="button"
                                className={actionClass}
                                onClick={() =>
                                    router.post(
                                        attach(publicationId).url,
                                        {
                                            revision_id: revisionId,
                                            media: item.asset.uuid,
                                            purpose: 'cover',
                                            alt_text: item.alt,
                                            caption: item.caption ?? '',
                                            credit: item.credit,
                                        },
                                        options,
                                    )
                                }
                            >
                                Usar como capa
                            </button>
                        </>
                    )}
                    <button
                        type="button"
                        className={actionClass}
                        onClick={() =>
                            router.delete(detach(item.id).url, options)
                        }
                    >
                        Remover desta revisão
                    </button>
                </div>
            )}
        </li>
    );
}

function LibraryCard({
    asset,
    canAttach,
    rightsTypes,
    publicationId,
    revisionId,
}: {
    asset: MediaAsset;
    canAttach: boolean;
    rightsTypes: string[];
    publicationId: number;
    revisionId: number;
}) {
    const [panel, setPanel] = useState<'' | 'attach' | 'rights' | 'block'>('');
    const usage = useForm<UsageText & { purpose: string }>({
        purpose: 'content',
        alt_text: '',
        caption: '',
        credit: asset.rights_holder ?? '',
    });
    const rightsForm = useForm<Rights>({
        rights_type: asset.rights_type ?? '',
        rights_holder: asset.rights_holder ?? '',
        license: asset.license ?? '',
        rights_notes: asset.rights_notes ?? '',
    });
    const blockForm = useForm({ reason: '' });
    const options = { preserveScroll: true, onSuccess: () => setPanel('') };
    const usable = asset.status === 'active' && asset.processing !== 'failed';
    return (
        <li className="space-y-3 rounded-xl border p-4">
            <Preview image={asset.preview} alt="" />
            <p className="truncate text-sm" title={asset.name ?? undefined}>
                {asset.name ?? 'Imagem sem nome'} · {asset.width}×{asset.height}
            </p>
            <AssetFlags asset={asset} />
            <div className="flex flex-wrap gap-2">
                {canAttach && usable && (
                    <button
                        type="button"
                        className={actionClass}
                        aria-expanded={panel === 'attach'}
                        onClick={() =>
                            setPanel(panel === 'attach' ? '' : 'attach')
                        }
                    >
                        Adicionar à revisão
                    </button>
                )}
                {asset.can.update && (
                    <button
                        type="button"
                        className={actionClass}
                        aria-expanded={panel === 'rights'}
                        onClick={() =>
                            setPanel(panel === 'rights' ? '' : 'rights')
                        }
                    >
                        Direitos de uso
                    </button>
                )}
                {asset.can.block &&
                    (asset.status === 'blocked' ? (
                        <button
                            type="button"
                            className={actionClass}
                            onClick={() =>
                                router.delete(unblock(asset.uuid).url, options)
                            }
                        >
                            Desbloquear
                        </button>
                    ) : (
                        <button
                            type="button"
                            className={actionClass}
                            aria-expanded={panel === 'block'}
                            onClick={() =>
                                setPanel(panel === 'block' ? '' : 'block')
                            }
                        >
                            Bloquear
                        </button>
                    ))}
                <a className={actionClass} href={original(asset.uuid).url}>
                    Baixar original
                </a>
                {asset.can.delete && (
                    <button
                        type="button"
                        className={actionClass}
                        onClick={() => {
                            if (
                                window.confirm(
                                    'Excluir esta imagem da biblioteca? Só é possível se ela não estiver em nenhuma revisão.',
                                )
                            )
                                router.delete(destroyAsset(asset.uuid).url, {
                                    preserveScroll: true,
                                });
                        }}
                    >
                        Excluir
                    </button>
                )}
            </div>
            {panel === 'attach' && (
                <form
                    className="space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        usage.transform((data) => ({
                            ...data,
                            revision_id: revisionId,
                            media: asset.uuid,
                        }));
                        usage.post(attach(publicationId).url, options);
                    }}
                >
                    <Errors errors={usage.errors} />
                    <label className="block space-y-1">
                        <span className="text-sm">Finalidade</span>
                        <select
                            className={fieldClass}
                            value={usage.data.purpose}
                            onChange={(e) =>
                                usage.setData('purpose', e.target.value)
                            }
                        >
                            <option value="content">Imagem do conteúdo</option>
                            <option value="cover">
                                Capa (substitui a atual)
                            </option>
                        </select>
                    </label>
                    <TextFields
                        data={usage.data}
                        change={(key, value) => usage.setData(key, value)}
                    />
                    <button className={actionClass} disabled={usage.processing}>
                        Adicionar
                    </button>
                </form>
            )}
            {panel === 'rights' && (
                <form
                    className="space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        rightsForm.patch(saveRights(asset.uuid).url, options);
                    }}
                >
                    <Errors errors={rightsForm.errors} />
                    <RightsFields
                        data={rightsForm.data}
                        change={(key, value) => rightsForm.setData(key, value)}
                        types={rightsTypes}
                    />
                    <button
                        className={actionClass}
                        disabled={rightsForm.processing}
                    >
                        Salvar direitos
                    </button>
                </form>
            )}
            {panel === 'block' && (
                <form
                    className="space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        blockForm.post(block(asset.uuid).url, options);
                    }}
                >
                    <Errors errors={blockForm.errors} />
                    <label className="block space-y-1">
                        <span className="text-sm">
                            Motivo do bloqueio (retira a imagem de páginas
                            públicas)
                        </span>
                        <input
                            className={fieldClass}
                            required
                            maxLength={500}
                            value={blockForm.data.reason}
                            onChange={(e) =>
                                blockForm.setData('reason', e.target.value)
                            }
                        />
                    </label>
                    <button
                        className={actionClass}
                        disabled={blockForm.processing}
                    >
                        Confirmar bloqueio
                    </button>
                </form>
            )}
        </li>
    );
}

export default function EditorialMedia({
    publicationId,
    revisionId,
    items,
    library,
    rightsTypes,
    canEdit,
    uploadLimitMb,
}: {
    publicationId: number;
    revisionId: number;
    items: RevisionMediaItem[];
    library: MediaAsset[];
    rightsTypes: string[];
    canEdit: boolean;
    uploadLimitMb: number;
}) {
    const form = useForm<Rights & { file: File | null }>({
        file: null,
        rights_type: '',
        rights_holder: '',
        license: '',
        rights_notes: '',
    });
    const [sizeError, setSizeError] = useState('');
    const contents = items.filter((item) => item.purpose === 'content');
    return (
        <section
            className="space-y-6 rounded-xl border p-6"
            aria-labelledby="media-heading"
        >
            <h2 id="media-heading" className="text-xl font-semibold">
                Imagens
            </h2>
            {!canEdit && (
                <p role="status" className="text-sm text-muted-foreground">
                    As imagens desta revisão estão congeladas. Para alterá-las,
                    crie uma nova revisão: ela começa com uma cópia deste
                    conjunto, e a edição publicada não muda.
                </p>
            )}
            {items.length === 0 ? (
                <p className="text-muted-foreground">
                    Esta revisão ainda não tem imagens.
                </p>
            ) : (
                <ul className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {items.map((item) => (
                        <UsageCard
                            key={item.id}
                            item={item}
                            canEdit={canEdit}
                            first={contents[0]?.id === item.id}
                            last={contents[contents.length - 1]?.id === item.id}
                            publicationId={publicationId}
                            revisionId={revisionId}
                        />
                    ))}
                </ul>
            )}
            <form
                className="space-y-4 rounded-lg border p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    if (
                        form.data.file &&
                        form.data.file.size > uploadLimitMb * 1024 * 1024
                    ) {
                        setSizeError(
                            `A imagem pode ter no máximo ${uploadLimitMb} MB neste servidor.`,
                        );
                        return;
                    }
                    setSizeError('');
                    form.post(upload().url, {
                        forceFormData: true,
                        preserveScroll: true,
                        onSuccess: () => form.reset(),
                    });
                }}
            >
                <h3 className="font-medium">Enviar imagem para a biblioteca</h3>
                <Errors
                    errors={
                        sizeError
                            ? { file: sizeError, ...form.errors }
                            : form.errors
                    }
                />
                <label className="block space-y-1">
                    <span className="text-sm">
                        JPEG, PNG ou WebP, até {uploadLimitMb} MB. O arquivo
                        original fica privado.
                    </span>
                    <input
                        className={fieldClass}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        onChange={(e) =>
                            form.setData('file', e.target.files?.[0] ?? null)
                        }
                    />
                </label>
                <RightsFields
                    data={form.data}
                    change={(key, value) => form.setData(key, value)}
                    types={rightsTypes}
                />
                {form.progress && (
                    <progress
                        value={form.progress.percentage}
                        max={100}
                        className="w-full"
                    >
                        {form.progress.percentage}%
                    </progress>
                )}
                <button className={actionClass} disabled={form.processing}>
                    {form.processing ? 'Enviando…' : 'Enviar imagem'}
                </button>
            </form>
            <div className="space-y-3">
                <h3 className="font-medium">Biblioteca</h3>
                {library.length === 0 ? (
                    <p className="text-muted-foreground">
                        Nenhuma imagem disponível para você ainda.
                    </p>
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {library.map((asset) => (
                            <LibraryCard
                                key={asset.uuid}
                                asset={asset}
                                canAttach={canEdit}
                                rightsTypes={rightsTypes}
                                publicationId={publicationId}
                                revisionId={revisionId}
                            />
                        ))}
                    </ul>
                )}
            </div>
        </section>
    );
}
