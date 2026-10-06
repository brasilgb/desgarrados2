import { Head } from '@inertiajs/react';
import type { CSSProperties } from 'react';
import ebookCover from './crescer-seguro-capa.webp';
import './pagina-de-obrigado.css';

export default function PaginaDeObrigado() {
    return (
        <>
            <Head title="Crescer Seguro Obrigado">
                <meta head-key="robots" name="robots" content="noindex" />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link
                    rel="stylesheet"
                    href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&amp;family=Figtree:wght@400;500;600&amp;display=swap"
                />
            </Head>
            <main className="ebook-thanks">
                <div className="wrap">
                    <div className="card">
                        <div className="ok">✓</div>
                        <div className="eyebrow">Compra confirmada</div>
                        <h1>Obrigado! Seu guia Crescer Seguro já é seu.</h1>
                        <p className="lead">
                            Enviamos o link de acesso para o e-mail que você
                            usou na compra. Se preferir, acesse agora mesmo pelo
                            botão abaixo.
                        </p>
                        <a
                            className="btn"
                            href="https://dashboard.kiwify.com.br/"
                        >
                            Acessar meu e-book
                        </a>
                        <p className="small">
                            Não encontrou o e-mail? Confira as pastas de spam e
                            promoções.
                        </p>
                        <img
                            className="cover"
                            src={ebookCover}
                            alt="Capa do e-book Crescer Seguro"
                        />
                    </div>

                    <h2
                        style={{
                            fontSize: '24px',
                            marginTop: '44px',
                            textAlign: 'center',
                        }}
                    >
                        Como aproveitar melhor o guia
                    </h2>
                    <div className="steps">
                        <div
                            className="step"
                            style={{ '--c': 'var(--orange)' } as CSSProperties}
                        >
                            <div className="n">1</div>
                            <div>
                                <h3>Comece pelo sumário</h3>
                                <p>
                                    Escolha a pergunta que mais combina com o
                                    momento do seu filho. Cada página leva
                                    poucos minutos para ler.
                                </p>
                            </div>
                        </div>
                        <div
                            className="step"
                            style={{ '--c': 'var(--teal)' } as CSSProperties}
                        >
                            <div className="n">2</div>
                            <div>
                                <h3>Use a frase pronta hoje</h3>
                                <p>
                                    No fim de cada página há uma “frase que
                                    ajuda”. Experimente usá-la na próxima
                                    conversa com seu filho.
                                </p>
                            </div>
                        </div>
                        <div
                            className="step"
                            style={{ '--c': 'var(--violet)' } as CSSProperties}
                        >
                            <div className="n">3</div>
                            <div>
                                <h3>Imprima o checklist</h3>
                                <p>
                                    A última página traz o checklist semanal dos
                                    pais. Deixe-o na geladeira para lembrar dos
                                    pequenos hábitos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <footer>
                        Dúvidas sobre o acesso? Responda o e-mail de compra que
                        ajudamos você.
                        <br />
                        Crescer Seguro · O guia dos pais para escola, amizades e
                        emoções
                    </footer>
                </div>
            </main>
        </>
    );
}
