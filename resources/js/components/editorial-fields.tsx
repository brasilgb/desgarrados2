import { useId, useState } from 'react';
import { lookup } from '@/routes/editorial';
import type { Content, Option } from '@/types/editorial';
import InputError from '@/components/input-error';

export const fieldClass =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-foreground';
export const actionClass =
    'rounded-lg border px-4 py-2 text-sm font-medium hover:bg-accent disabled:opacity-50';

export function Errors({ errors }: { errors: Record<string, string> }) {
    return Object.keys(errors).length > 0 ? (
        <div role="alert" className="rounded-lg border border-destructive p-4">
            <p>Revise os campos para continuar.</p>
            {Object.entries(errors).map(([key, message]) => (
                <InputError key={key} message={message} />
            ))}
        </div>
    ) : null;
}
export function ContentFields({
    data,
    change,
    type,
    disabled = false,
}: {
    data: Content;
    change: <K extends keyof Content>(key: K, value: Content[K]) => void;
    type: string;
    disabled?: boolean;
}) {
    const id = useId();
    const guidance: Record<string, string> = {
        article:
            'Apresente o tema com contexto e organize as informações por tópicos ou parágrafos.',
        news: 'Priorize os fatos, as datas e as fontes que permitem confirmar as informações.',
        chronicle:
            'Uma cena, voz ou lembrança pode conduzir o texto; identifique pessoas e lugares com contexto.',
        causo: 'Conte a situação, quem narra e onde acontece. Preserve a oralidade sem expor dados pessoais sem autorização.',
        cultural_history:
            'Registre práticas, períodos, lugares e fontes; diferencie memória oral de informação documentada.',
        personal_story:
            'Organize a narrativa pela experiência de quem conta e confirme autorização para nomes e detalhes pessoais.',
        migration_story:
            'Inclua os lugares de partida e chegada e, se possível, referências temporais da travessia.',
        memory: 'Para poema, use uma linha por verso e deixe uma linha em branco entre estrofes. Essas quebras serão preservadas na leitura pública.',
    };
    return (
        <fieldset disabled={disabled} className="space-y-5">
            <legend className="mb-4 font-semibold">
                Texto e fontes desta versão
            </legend>
            <p className="rounded-lg bg-muted p-3 text-sm" role="note">
                {guidance[type] ??
                    'Inclua contexto, autoria e referências úteis para a edição.'}
            </p>
            {(['title', 'summary', 'public_byline'] as const).map((key) => (
                <label
                    key={key}
                    className="block space-y-2"
                    htmlFor={`${id}-${key}`}
                >
                    <span>
                        {
                            {
                                title: 'Título',
                                summary: 'Resumo',
                                public_byline: 'Assinatura pública',
                            }[key]
                        }
                    </span>
                    <input
                        id={`${id}-${key}`}
                        className={fieldClass}
                        value={data[key]}
                        required={key === 'title'}
                        maxLength={
                            key === 'title'
                                ? 200
                                : key === 'summary'
                                  ? 500
                                  : 120
                        }
                        onChange={(e) => change(key, e.target.value)}
                    />
                </label>
            ))}
            <label className="block space-y-2" htmlFor={`${id}-body`}>
                <span>Texto da publicação</span>
                <textarea
                    id={`${id}-body`}
                    className={fieldClass}
                    rows={14}
                    required
                    aria-describedby={`${id}-body-hint`}
                    value={data.body}
                    onChange={(e) => change('body', e.target.value)}
                />
                <span
                    id={`${id}-body-hint`}
                    className="block text-sm text-muted-foreground"
                >
                    A formatação digitada é preservada. Em prosa, separe
                    parágrafos com uma linha em branco; em versos, use uma linha
                    por verso e uma linha vazia entre estrofes.
                </span>
            </label>
            <details className="rounded-lg border p-4" open>
                <summary className="cursor-pointer font-medium">
                    Autoria e direitos de uso
                </summary>
                <p className="my-3 text-sm text-muted-foreground">
                    A publicação fica bloqueada até haver comprovação
                    suficiente. Crédito ou fonte, por si só, não concede direito
                    de uso.
                </p>
                <div className="grid gap-4 sm:grid-cols-2">
                    <label className="block space-y-2">
                        <span>Situação autoral</span>
                        <select
                            className={fieldClass}
                            value={data.rights_status}
                            onChange={(e) =>
                                change(
                                    'rights_status',
                                    e.target.value as Content['rights_status'],
                                )
                            }
                        >
                            <option value="unverified">
                                Ainda não comprovada
                            </option>
                            <option value="original">
                                Texto original do próprio autor
                            </option>
                            <option value="authorized">
                                Autorizado pelo titular
                            </option>
                            <option value="public_domain">
                                Domínio público
                            </option>
                            <option value="licensed">Licenciado</option>
                        </select>
                    </label>
                    {data.rights_status === 'original' ? (
                        <label className="block space-y-2">
                            <span>Autor da obra</span>
                            <input
                                className={fieldClass}
                                maxLength={200}
                                required
                                value={data.rights_author}
                                onChange={(e) =>
                                    change('rights_author', e.target.value)
                                }
                            />
                        </label>
                    ) : data.rights_status !== 'unverified' ? (
                        <label className="block space-y-2">
                            <span>Titular dos direitos (se conhecido)</span>
                            <input
                                className={fieldClass}
                                maxLength={200}
                                required={
                                    data.rights_status === 'authorized' ||
                                    data.rights_status === 'licensed'
                                }
                                value={data.rights_holder}
                                onChange={(e) =>
                                    change('rights_holder', e.target.value)
                                }
                            />
                        </label>
                    ) : null}
                    {data.rights_status !== 'original' &&
                        data.rights_status !== 'unverified' && (
                            <>
                                <label className="block space-y-2">
                                    <span>Fundamento de uso</span>
                                    <input
                                        className={fieldClass}
                                        maxLength={500}
                                        required
                                        value={data.rights_basis}
                                        onChange={(e) =>
                                            change(
                                                'rights_basis',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Ex.: autorização assinada em 10/10/2026"
                                    />
                                </label>
                                <label className="block space-y-2">
                                    <span>
                                        Referência da autorização/licença ou
                                        fonte da obra
                                    </span>
                                    <input
                                        className={fieldClass}
                                        maxLength={2048}
                                        required
                                        value={data.rights_reference}
                                        onChange={(e) =>
                                            change(
                                                'rights_reference',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </label>
                            </>
                        )}
                    <label className="block space-y-2">
                        <span>Restrições de uso (opcional)</span>
                        <textarea
                            className={fieldClass}
                            maxLength={5000}
                            value={data.rights_restrictions}
                            onChange={(e) =>
                                change('rights_restrictions', e.target.value)
                            }
                        />
                    </label>
                </div>
            </details>
            <details className="rounded-lg border p-4">
                <summary className="cursor-pointer font-medium">
                    Fontes editoriais ({data.sources.length})
                </summary>
                <p className="my-3 text-sm text-muted-foreground">
                    Registre de onde vieram as informações. Para obras de
                    terceiros, crédito sozinho não substitui autorização ou
                    licença de uso.
                </p>
                <div className="space-y-4">
                    {data.sources.map((source, index) => (
                        <fieldset
                            key={index}
                            className="space-y-3 rounded-lg border p-4"
                        >
                            <legend>Fonte {index + 1}</legend>
                            {(
                                [
                                    'title',
                                    'url',
                                    'attribution',
                                    'accessed_at',
                                ] as const
                            ).map((key) => (
                                <label key={key} className="block space-y-1">
                                    <span>
                                        {
                                            {
                                                title: 'Título',
                                                url: 'URL opcional',
                                                attribution: 'Atribuição',
                                                accessed_at: 'Data de acesso',
                                            }[key]
                                        }
                                    </span>
                                    <input
                                        className={fieldClass}
                                        type={
                                            key === 'accessed_at'
                                                ? 'date'
                                                : key === 'url'
                                                  ? 'url'
                                                  : 'text'
                                        }
                                        required={key === 'title'}
                                        value={source[key] ?? ''}
                                        onChange={(e) =>
                                            change(
                                                'sources',
                                                data.sources.map((item, i) =>
                                                    i === index
                                                        ? {
                                                              ...item,
                                                              [key]: e.target
                                                                  .value,
                                                          }
                                                        : item,
                                                ),
                                            )
                                        }
                                    />
                                </label>
                            ))}
                            <button
                                type="button"
                                className={actionClass}
                                onClick={() =>
                                    change(
                                        'sources',
                                        data.sources.filter(
                                            (_, i) => i !== index,
                                        ),
                                    )
                                }
                            >
                                Remover fonte
                            </button>
                        </fieldset>
                    ))}
                    <button
                        type="button"
                        className={actionClass}
                        onClick={() =>
                            change('sources', [
                                ...data.sources,
                                {
                                    title: '',
                                    url: '',
                                    attribution: '',
                                    accessed_at: '',
                                },
                            ])
                        }
                    >
                        Adicionar fonte
                    </button>
                </div>
            </details>
        </fieldset>
    );
}
export function Lookup({
    kind,
    label,
    selected,
    onChange,
    multiple = false,
}: {
    kind: string;
    label: string;
    selected: Option[];
    onChange: (options: Option[]) => void;
    multiple?: boolean;
}) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Option[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const id = useId();
    async function search() {
        setLoading(true);
        setError('');
        try {
            const response = await fetch(
                lookup({ kind }, { query: { q: query } }).url,
                { headers: { Accept: 'application/json' } },
            );
            if (!response.ok) throw new Error('Falha na busca');
            setResults((await response.json()) as Option[]);
        } catch {
            setError('Não foi possível buscar. Tente novamente.');
        } finally {
            setLoading(false);
        }
    }
    return (
        <fieldset className="space-y-2 rounded-lg border p-3">
            <legend className="px-1 font-medium">{label}</legend>
            <div className="flex gap-2">
                <label className="sr-only" htmlFor={id}>
                    Buscar {label}
                </label>
                <input
                    id={id}
                    className={fieldClass}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Buscar por nome"
                />
                <button
                    type="button"
                    className={actionClass}
                    disabled={loading}
                    onClick={() => void search()}
                >
                    {loading ? 'Buscando…' : 'Buscar'}
                </button>
            </div>
            {error && <p role="alert">{error}</p>}
            <div aria-live="polite" className="flex flex-wrap gap-2">
                {selected.map((option) => (
                    <button
                        type="button"
                        className={actionClass}
                        key={option.id}
                        onClick={() =>
                            onChange(
                                selected.filter(
                                    (item) => item.id !== option.id,
                                ),
                            )
                        }
                    >
                        {option.name} ×<span className="sr-only"> Remover</span>
                    </button>
                ))}
            </div>
            {results.length > 0 && (
                <ul className="max-h-48 overflow-auto">
                    {results.map((option) => (
                        <li key={option.id}>
                            <button
                                type="button"
                                disabled={selected.some(
                                    (item) => item.id === option.id,
                                )}
                                className="w-full rounded p-2 text-left hover:bg-accent disabled:opacity-50"
                                onClick={() => {
                                    onChange(
                                        multiple
                                            ? [...selected, option]
                                            : [option],
                                    );
                                    setResults([]);
                                }}
                            >
                                {option.name}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {!loading && query && results.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    Busque para consultar até 20 resultados.
                </p>
            )}
        </fieldset>
    );
}
