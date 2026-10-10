import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';
export default function Denied() {
    return (
        <section role="alert" className="mx-auto max-w-2xl space-y-5 p-8">
            <Head title="Acesso restrito">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <p className="text-sm text-muted-foreground">
                Redação · acesso restrito
            </p>
            <h1 className="text-3xl font-semibold">
                Seu papel não permite esta operação.
            </h1>
            <p>
                O trabalho editorial respeita a autoria e exige outro editor
                para aprovar uma revisão própria. Entre com uma conta autorizada
                para continuar.
            </p>
            <Link className="underline" href={home()}>
                Voltar ao Desgarrados
            </Link>
        </section>
    );
}
