// Processo do renderer Inertia SSR para o contêiner `ssr` (um único processo, sem cluster).
// O Node como PID 1 ignora SIGTERM sem handler explícito; sem isto, `docker stop` aguardaria o SIGKILL.
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

const bundle = resolve(
    process.env.INERTIA_SSR_BUNDLE ?? 'bootstrap/ssr/app.js',
);

for (const signal of ['SIGTERM', 'SIGINT']) {
    process.once(signal, () => {
        console.log(`[ssr] ${signal} recebido; encerrando.`);
        process.exit(0);
    });
}

process.on('unhandledRejection', (reason) => {
    console.error(
        '[ssr] rejeição não tratada:',
        reason instanceof Error ? reason.message : reason,
    );
    process.exit(1);
});

await import(pathToFileURL(bundle).href);
