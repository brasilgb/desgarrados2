import { Head, Link } from '@inertiajs/react';
import { index as editorial } from '@/routes/editorial';
import { roles } from '@/routes/admin';
import { index as territory } from '@/routes/territory';

export default function Administration({
    canManageRoles,
    canEdit,
}: {
    canManageRoles: boolean;
    canEdit: boolean;
}) {
    return (
        <>
            <Head title="Administração" />
            <section className="flex flex-col gap-6 p-6">
                <h1 className="text-3xl font-semibold">Administração</h1>
                <p className="max-w-xl text-muted-foreground">
                    Acesso de trabalho conforme seus papéis. Cada operação tem
                    sua própria autorização.
                </p>
                <div className="grid gap-4 md:grid-cols-2">
                    {canEdit && (
                        <Link
                            href={editorial()}
                            className="rounded-xl border p-6"
                        >
                            <h2 className="font-semibold">Redação</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Acervo, revisões, mídias e comprovantes de
                                direitos.
                            </p>
                        </Link>
                    )}
                    {canEdit && (
                        <Link
                            href="/administracao/editorial/regioes"
                            className="rounded-xl border p-6"
                        >
                            <h2 className="font-semibold">Regiões</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Vínculos territoriais e páginas culturais.
                            </p>
                        </Link>
                    )}
                    {canEdit && (
                        <Link
                            href="/administracao/editorial/destaques"
                            className="rounded-xl border p-6"
                        >
                            <h2 className="font-semibold">
                                Destaques do acervo
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Programação editorial temporária e regional.
                            </p>
                        </Link>
                    )}
                    <Link href={territory()} className="rounded-xl border p-6">
                        <h2 className="font-semibold">
                            Consultar o território
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Estados, municípios e vínculos regionais.
                        </p>
                    </Link>
                    {canManageRoles && (
                        <Link
                            href="/administracao/usuarios"
                            className="rounded-xl border p-6"
                        >
                            <h2 className="font-semibold">
                                Usuários e permissões
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Contas, acessos, papéis e auditoria
                                administrativa.
                            </p>
                        </Link>
                    )}
                    {canManageRoles && (
                        <Link href={roles()} className="rounded-xl border p-6">
                            <h2 className="font-semibold">
                                Auditoria de papéis
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Concessões e revogações com justificativa.
                            </p>
                        </Link>
                    )}
                </div>
            </section>
        </>
    );
}
