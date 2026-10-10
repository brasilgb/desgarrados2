// Health check do contêiner SSR: consulta somente a rota /health do renderer local.
const controller = new AbortController();
setTimeout(() => controller.abort(), 2000);

try {
    const response = await fetch('http://127.0.0.1:13714/health', {
        signal: controller.signal,
    });
    process.exit(response.ok ? 0 : 1);
} catch {
    process.exit(1);
}
