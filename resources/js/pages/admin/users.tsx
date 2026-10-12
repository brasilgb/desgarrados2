import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { TerritoryPagination } from '@/components/territory-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { roles as rolesRoute } from '@/routes/admin';
import type { Paginated } from '@/types/territory';

type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_active: boolean;
    roles: { id: number; name: string }[];
};
type Audit = {
    id: number;
    actor: { name: string } | null;
    target_user_id: number | null;
    action: string;
    changes: Record<string, unknown> | null;
    created_at: string;
};

function AccountEditor({ user }: { user: User }) {
    const form = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
    });
    const status = useForm({ reason: '' });
    return (
        <details className="rounded-xl border p-4">
            <summary className="cursor-pointer font-medium">
                {user.name} · {user.email} ·{' '}
                {user.is_active ? 'Ativa' : 'Desativada'}
            </summary>
            <div className="mt-3 space-y-2 text-sm">
                <p>
                    {user.email_verified_at
                        ? 'E-mail verificado'
                        : 'Aguardando verificação do e-mail'}
                </p>
                <p>
                    Papéis:{' '}
                    {user.roles.map((role) => role.name).join(', ') ||
                        'Usuário comum'}
                </p>
            </div>
            <form
                className="mt-4 grid gap-3 sm:grid-cols-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.patch(`/administracao/usuarios/${user.id}`);
                }}
            >
                <Label>
                    Nome
                    <Input
                        required
                        maxLength={120}
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                </Label>
                <Label>
                    E-mail
                    <Input
                        required
                        type="email"
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                    />
                </Label>
                <Label>
                    Nova senha (opcional)
                    <Input
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                </Label>
                <Label>
                    Confirme a nova senha
                    <Input
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                </Label>
                {Object.values(form.errors).map((error) => (
                    <p
                        role="alert"
                        className="text-sm text-red-700"
                        key={error}
                    >
                        {error}
                    </p>
                ))}
                <Button disabled={form.processing} className="sm:col-span-2">
                    Salvar dados
                </Button>
            </form>
            <form
                className="mt-4 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-end"
                onSubmit={(event) => {
                    event.preventDefault();
                    status.patch(`/administracao/usuarios/${user.id}/status`, {
                        onSuccess: () => status.reset(),
                    });
                }}
            >
                <Label className="flex-1">
                    Justificativa para {user.is_active ? 'desativar' : 'ativar'}
                    <Input
                        required
                        maxLength={500}
                        value={status.data.reason}
                        onChange={(event) =>
                            status.setData('reason', event.target.value)
                        }
                    />
                </Label>
                {status.errors.reason && (
                    <p role="alert">{status.errors.reason}</p>
                )}
                <Button variant="outline" disabled={status.processing}>
                    {user.is_active ? 'Desativar conta' : 'Ativar conta'}
                </Button>
            </form>
        </details>
    );
}

export default function Users({
    users,
    filters,
    audits,
}: {
    users: Paginated<User>;
    filters: { q?: string };
    audits: Audit[];
}) {
    const [query, setQuery] = useState(filters.q ?? '');
    return (
        <>
            <Head title="Usuários e permissões">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <main className="mx-auto flex max-w-6xl flex-col gap-6 p-6">
                <nav className="flex flex-wrap gap-4">
                    <Link href="/administracao">← Administração</Link>
                    <Link href={rolesRoute()}>Papéis</Link>
                </nav>
                <header>
                    <h1 className="text-3xl font-semibold">
                        Usuários e permissões
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Contas novas precisam verificar o e-mail; papéis são
                        concedidos separadamente com justificativa e auditoria.
                    </p>
                </header>
                <form
                    className="flex flex-wrap gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            '/administracao/usuarios',
                            { q: query },
                            { preserveState: true },
                        );
                    }}
                >
                    <Label className="min-w-60 flex-1">
                        Pesquisar por nome ou e-mail
                        <Input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                        />
                    </Label>
                    <Button className="self-end">Pesquisar</Button>
                </form>
                <section className="space-y-3" aria-label="Contas cadastradas">
                    {users.data.map((user) => (
                        <AccountEditor key={user.id} user={user} />
                    ))}
                    {users.data.length === 0 && (
                        <p>Nenhuma conta encontrada.</p>
                    )}
                </section>
                <TerritoryPagination
                    current={users.current_page}
                    last={users.last_page}
                    pageUrl={(page) =>
                        `/administracao/usuarios?q=${encodeURIComponent(query)}&page=${page}`
                    }
                />
                <section className="rounded-xl border p-5">
                    <h2 className="text-xl font-semibold">Cadastrar usuário</h2>
                    <p className="my-2 text-sm text-muted-foreground">
                        A conta começa sem papéis especiais e receberá
                        verificação de e-mail.
                    </p>
                    <CreateUser />
                </section>
                <section className="space-y-3">
                    <h2 className="text-xl font-semibold">
                        Auditoria administrativa recente
                    </h2>
                    {audits.map((audit) => (
                        <article
                            className="rounded border p-4 text-sm"
                            key={audit.id}
                        >
                            <p>
                                {audit.action} · conta #
                                {audit.target_user_id ?? 'removida'} · por{' '}
                                {audit.actor?.name ?? 'operação inicial'} ·{' '}
                                {audit.created_at}
                            </p>
                        </article>
                    ))}
                </section>
            </main>
        </>
    );
}

function CreateUser() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    return (
        <form
            className="grid gap-3 sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/administracao/usuarios', {
                    onSuccess: () => form.reset(),
                });
            }}
        >
            <Label>
                Nome
                <Input
                    required
                    maxLength={120}
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                />
            </Label>
            <Label>
                E-mail
                <Input
                    required
                    type="email"
                    value={form.data.email}
                    onChange={(event) =>
                        form.setData('email', event.target.value)
                    }
                />
            </Label>
            <Label>
                Senha inicial
                <Input
                    required
                    type="password"
                    autoComplete="new-password"
                    value={form.data.password}
                    onChange={(event) =>
                        form.setData('password', event.target.value)
                    }
                />
            </Label>
            <Label>
                Confirme a senha
                <Input
                    required
                    type="password"
                    autoComplete="new-password"
                    value={form.data.password_confirmation}
                    onChange={(event) =>
                        form.setData(
                            'password_confirmation',
                            event.target.value,
                        )
                    }
                />
            </Label>
            {Object.values(form.errors).map((error) => (
                <p role="alert" className="text-sm text-red-700" key={error}>
                    {error}
                </p>
            ))}
            <Button disabled={form.processing} className="sm:col-span-2">
                Cadastrar usuário
            </Button>
        </form>
    );
}
