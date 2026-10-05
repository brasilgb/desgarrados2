import { Form, Head, Link } from '@inertiajs/react';
import { TerritoryPagination } from '@/components/territory-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { municipality, state as showState } from '@/routes/territory';
import type {
    Municipality,
    Paginated,
    TerritoryState,
} from '@/types/territory';

export default function State({
    state,
    municipalities,
    filters,
}: {
    state: TerritoryState;
    municipalities: Paginated<Municipality>;
    filters: { q: string };
}) {
    return (
        <>
            <Head title={state.name} />
            <p className="text-sm tracking-widest uppercase">
                {state.abbreviation} · {municipalities.total} municípios
                encontrados
            </p>
            <h1 className="mt-3 font-serif text-4xl sm:text-5xl">
                {state.name}
            </h1>
            <p className="mt-5 opacity-80">
                Encontre uma cidade e conheça seus vínculos com o território.
            </p>
            <Form
                {...showState.form(state.slug)}
                className="mt-8 flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end"
            >
                {({ errors }) => (
                    <>
                        <div className="flex-1">
                            <Label htmlFor="municipality-search">
                                Buscar município
                            </Label>
                            <Input
                                id="municipality-search"
                                name="q"
                                defaultValue={filters.q}
                                placeholder="Nome do município"
                                maxLength={100}
                                className="mt-2"
                                aria-describedby={
                                    errors.q ? 'search-error' : undefined
                                }
                            />
                            {errors.q && (
                                <p
                                    id="search-error"
                                    className="text-sm text-red-700 dark:text-red-300"
                                >
                                    {errors.q}
                                </p>
                            )}
                        </div>
                        <Button type="submit">Buscar</Button>
                    </>
                )}
            </Form>
            <div className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {municipalities.data.map((item) => (
                    <Link
                        key={item.id}
                        href={municipality({
                            state: state.slug,
                            municipality: item.slug,
                        })}
                        className="rounded-xl border border-current/20 px-5 py-5 hover:bg-white/50 dark:hover:bg-white/10"
                    >
                        <h2 className="font-medium">{item.name}</h2>
                        <span className="text-xs opacity-70">
                            Conhecer este lugar →
                        </span>
                    </Link>
                ))}
            </div>
            {municipalities.data.length === 0 && (
                <p className="py-10" role="status">
                    Nenhum município encontrado. Tente outro nome.
                </p>
            )}
            <TerritoryPagination
                current={municipalities.current_page}
                last={municipalities.last_page}
                pageUrl={(page) =>
                    showState.url(state.slug, { query: { page, q: filters.q } })
                }
            />
        </>
    );
}
