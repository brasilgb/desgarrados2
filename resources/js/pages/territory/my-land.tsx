import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { myLand, state } from '@/routes/territory';
import type { TerritoryState } from '@/types/territory';

export default function MyLand({
    states,
    selectedState,
}: {
    states: TerritoryState[];
    selectedState: string | null;
}) {
    const selected = states.find((item) => item.slug === selectedState);
    return (
        <>
            <Head title="Minha terra" />
            <p className="text-sm tracking-widest uppercase">
                O lugar que você leva consigo
            </p>
            <h1 className="mt-3 font-serif text-4xl sm:text-6xl">
                Qual terra você quer conhecer?
            </h1>
            <p className="mt-5 max-w-2xl text-lg leading-relaxed opacity-80">
                Pode ser onde nasceu, onde vive ou um lugar pelo qual sente
                carinho. Aqui, a escolha é apenas um caminho de leitura.
            </p>
            <Form
                {...myLand.form()}
                className="mt-8 flex max-w-xl flex-col gap-4"
            >
                <Label htmlFor="land-state">Escolha um estado</Label>
                <select
                    id="land-state"
                    name="state"
                    defaultValue={selectedState ?? ''}
                    className="rounded-lg border border-current/30 bg-[#f5f2e9] p-3 dark:bg-[#15251f]"
                >
                    <option value="">Selecione um estado</option>
                    {states.map((item) => (
                        <option key={item.id} value={item.slug}>
                            {item.name}
                        </option>
                    ))}
                </select>
                <Button type="submit" className="self-start">
                    Escolher território
                </Button>
            </Form>
            {selected && (
                <div className="mt-8 rounded-xl border border-current/20 p-6">
                    <h2 className="font-serif text-2xl">{selected.name}</h2>
                    <p className="mt-2">
                        {selected.municipalities_count} municípios para
                        explorar.
                    </p>
                    <Link
                        href={state(selected.slug)}
                        className="mt-4 inline-block font-medium underline underline-offset-4"
                    >
                        Encontrar meu município →
                    </Link>
                </div>
            )}
            <p className="mt-8 max-w-xl text-sm leading-relaxed opacity-75">
                Não solicitamos geolocalização. Essa seleção fica na navegação
                atual e não grava origem, residência ou preferências no seu
                perfil.
            </p>
        </>
    );
}
