import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { TerritoryPagination } from '@/components/territory-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { grant, revoke, roles as rolesRoute } from '@/routes/admin';
import type { Paginated } from '@/types/territory';

type Role = { id: number; code: string; name: string };
type RoleUser = {
    id: number;
    name: string;
    email_verified_at: string | null;
    roles: Role[];
};
type Audit = {
    id: number;
    target_user_id: number | null;
    actor_id: number | null;
    role_code: string;
    action: string;
    reason: string;
    created_at: string;
};

function AssignmentForm({ user, roles }: { user: RoleUser; roles: Role[] }) {
    const [roleId, setRoleId] = useState(roles[0]?.id ?? 0);
    const [intent, setIntent] = useState('grant');
    if (!roles.length) return <p>Catálogo de papéis indisponível.</p>;
    return (
        <Form
            {...(intent === 'grant' ? grant : revoke).form({
                user: user.id,
                role: roleId,
            })}
            className="mt-4 flex flex-col gap-3"
        >
            {({ errors, processing, recentlySuccessful }) => (
                <>
                    <Label htmlFor={`role-${user.id}`}>Papel</Label>
                    <select
                        id={`role-${user.id}`}
                        value={roleId}
                        onChange={(event) =>
                            setRoleId(Number(event.target.value))
                        }
                        className="rounded border bg-background p-2"
                    >
                        {roles.map((role) => (
                            <option key={role.id} value={role.id}>
                                {role.name}
                            </option>
                        ))}
                    </select>
                    <Label htmlFor={`operation-${user.id}`}>Operação</Label>
                    <select
                        id={`operation-${user.id}`}
                        value={intent}
                        onChange={(event) => setIntent(event.target.value)}
                        className="rounded border bg-background p-2"
                    >
                        <option value="grant">Conceder</option>
                        <option value="revoke">Revogar</option>
                    </select>
                    <Label htmlFor={`reason-${user.id}`}>Justificativa</Label>
                    <Input
                        id={`reason-${user.id}`}
                        name="reason"
                        required
                        maxLength={500}
                        aria-describedby={`result-${user.id}`}
                    />
                    <div id={`result-${user.id}`} aria-live="polite">
                        {Object.entries(errors).map(([key, message]) => (
                            <p
                                key={key}
                                className="text-sm text-red-700 dark:text-red-300"
                            >
                                {message}
                            </p>
                        ))}
                        {recentlySuccessful && (
                            <p className="text-sm">Operação concluída.</p>
                        )}
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="self-start"
                    >
                        {processing ? 'Salvando…' : 'Confirmar operação'}
                    </Button>
                </>
            )}
        </Form>
    );
}

export default function Roles({
    roles,
    users,
    audits,
}: {
    roles: Role[];
    users: Paginated<RoleUser>;
    audits: Audit[];
}) {
    return (
        <>
            <Head title="Papéis e auditoria" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-3xl font-semibold">Papéis e auditoria</h1>
                <p className="max-w-2xl text-muted-foreground">
                    O usuário comum é o acesso base. Papéis adicionais são
                    cumulativos, sem concessão automática. O último
                    administrador verificado permanece protegido.
                </p>
                <section
                    aria-label="Usuários"
                    className="grid gap-4 lg:grid-cols-2"
                >
                    {users.data.map((user) => (
                        <details
                            key={user.id}
                            className="rounded-xl border p-5"
                        >
                            <summary className="cursor-pointer font-medium">
                                {user.name} · #{user.id}
                            </summary>
                            <p className="mt-3 text-sm">
                                {user.email_verified_at
                                    ? 'E-mail verificado'
                                    : 'E-mail não verificado'}
                            </p>
                            <p className="mt-2 text-sm">
                                {user.roles
                                    .map((role) => role.name)
                                    .join(', ') || 'Usuário comum'}
                            </p>
                            <AssignmentForm user={user} roles={roles} />
                        </details>
                    ))}
                </section>
                <TerritoryPagination
                    current={users.current_page}
                    last={users.last_page}
                    pageUrl={(page) => rolesRoute.url({ query: { page } })}
                />
                <section>
                    <h2 className="text-xl font-semibold">
                        Últimas alterações
                    </h2>
                    {audits.length === 0 && (
                        <p className="mt-3 text-sm text-muted-foreground">
                            Nenhuma alteração de papéis registrada.
                        </p>
                    )}
                    <ul className="mt-4 flex flex-col gap-3">
                        {audits.map((audit) => (
                            <li
                                key={audit.id}
                                className="rounded border p-4 text-sm"
                            >
                                <p>
                                    Usuário #
                                    {audit.target_user_id ?? 'excluído'} ·{' '}
                                    {audit.role_code} ·{' '}
                                    {audit.action === 'granted'
                                        ? 'concedido'
                                        : 'revogado'}
                                </p>
                                <p className="mt-1 text-muted-foreground">
                                    {audit.reason}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Responsável #
                                    {audit.actor_id ??
                                        'operação inicial / conta excluída'}{' '}
                                    · {audit.created_at}
                                </p>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </>
    );
}
