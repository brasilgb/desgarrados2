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
    disabled = false,
}: {
    data: Content;
    change: <K extends keyof Content>(key: K, value: Content[K]) => void;
    disabled?: boolean;
}) {
    const id = useId();
    return (
        <fieldset disabled={disabled} className="space-y-5">
            <legend className="mb-4 font-semibold">
                Texto e fontes desta versão
            </legend>
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
                <span>
                    Texto simples — separe parágrafos com uma linha em branco
                </span>
                <textarea
                    id={`${id}-body`}
                    className={fieldClass}
                    rows={14}
                    required
                    value={data.body}
                    onChange={(e) => change('body', e.target.value)}
                />
            </label>
            <div className="space-y-4">
                <h3 className="font-medium">Fontes editoriais</h3>
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
                                                          [key]: e.target.value,
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
                                    data.sources.filter((_, i) => i !== index),
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
