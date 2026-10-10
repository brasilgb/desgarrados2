import { Head, Link, useForm } from '@inertiajs/react';
import {
    ContentFields,
    Errors,
    actionClass,
    fieldClass,
} from '@/components/editorial-fields';
import { index, store } from '@/routes/editorial';
import type { Source } from '@/types/editorial';
import { labels } from '@/types/editorial';
export default function Create({ types }: { types: string[] }) {
    const form = useForm({
        type: 'memory',
        slug: '',
        title: '',
        summary: '',
        body: '',
        public_byline: '',
        sources: [] as Source[],
    });
    return (
        <section className="mx-auto w-full max-w-3xl space-y-6 p-6">
            <Head title="Nova publicação">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <Link href={index()}>← Redação</Link>
            <h1 className="text-3xl font-semibold">
                Uma nova história começa aqui
            </h1>
            <p className="text-muted-foreground">
                Memórias, pessoas e lugares. O conteúdo permanece privado até
                revisão e publicação.
            </p>
            <form
                className="space-y-6"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(store().url);
                }}
            >
                <Errors errors={form.errors} />
                <label className="block space-y-2">
                    <span>Tipo de conteúdo</span>
                    <select
                        className={fieldClass}
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                    >
                        {types.map((type) => (
                            <option key={type} value={type}>
                                {labels[type]}
                            </option>
                        ))}
                    </select>
                </label>
                <ContentFields
                    data={form.data}
                    change={(key, value) =>
                        form.setData((old) => ({ ...old, [key]: value }))
                    }
                    disabled={form.processing}
                />
                <label className="block space-y-2">
                    <span>Slug opcional (gerado do título quando vazio)</span>
                    <input
                        className={fieldClass}
                        value={form.data.slug}
                        onChange={(e) => form.setData('slug', e.target.value)}
                    />
                </label>
                <button className={actionClass} disabled={form.processing}>
                    {form.processing ? 'Salvando…' : 'Criar rascunho'}
                </button>
            </form>
        </section>
    );
}
