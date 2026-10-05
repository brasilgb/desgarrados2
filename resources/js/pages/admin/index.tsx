import { Head, Link } from '@inertiajs/react';
import { roles } from '@/routes/admin';
import { index as territory } from '@/routes/territory';

export default function Administration({
    canManageRoles,
}: {
    canManageRoles: boolean;
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
                    <Link href={territory()} className="rounded-xl border p-6">
                        <h2 className="font-semibold">
                            Consultar o território
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Estados, municípios e vínculos regionais.
                        </p>
                    </Link>
                    {canManageRoles && (
                        <Link href={roles()} className="rounded-xl border p-6">
                            <h2 className="font-semibold">
                                Papéis e auditoria
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
