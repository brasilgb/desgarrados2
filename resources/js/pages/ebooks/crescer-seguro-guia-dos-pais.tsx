import { Head } from '@inertiajs/react';
import type { CSSProperties } from 'react';
import ebookMockup from './crescer-seguro-mockup.webp';
import schoolPreview from './crescer-seguro-escola.webp';
import safetyPreview from './crescer-seguro-seguranca.webp';
import weeklyChecklist from './crescer-seguro-checklist.webp';
import './pagina-de-vendas.css';

export default function PaginaDeVendas() {
    return (
        <>
            <Head title="Crescer Seguro">
                <meta
                    head-key="description"
                    name="description"
                    content="Guia prático para pais: 16 respostas sobre adaptação escolar, amizades, telas e emoções na infância."
                />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link
                    rel="stylesheet"
                    href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&amp;family=Figtree:wght@400;500;600&amp;display=swap"
                />
            </Head>
            <main className="ebook-sales">
                <header className="hero">
                    <div className="wrap">
                        <div>
                            <div className="eyebrow">
                                Guia prático para pais
                            </div>
                            <h1>Crescer Seguro</h1>
                            <p className="lead">
                                As 16 dúvidas que mais preocupam os pais sobre
                                escola, amizades, telas e emoções, respondidas
                                com passos simples e frases prontas para usar
                                com seu filho.
                            </p>
                            <div className="cta">
                                <a
                                    className="btn"
                                    href="https://pay.kiwify.com.br/b1cEJF3"
                                >
                                    Quero o guia agora
                                </a>
                                <div className="price-inline">
                                    <s>R$ 37</s>
                                    <b>R$ 19,90</b>
                                </div>
                            </div>
                            <p className="small" style={{ marginTop: '12px' }}>
                                E-book em PDF · acesso imediato · garantia de 7
                                dias
                            </p>
                        </div>
                        <img
                            src={ebookMockup}
                            alt="Capa do e-book Crescer Seguro ao lado de uma página interna"
                        />
                    </div>
                </header>

                <section>
                    <div className="wrap center">
                        <h2 style={{ fontSize: 'clamp(28px,4vw,40px)' }}>
                            Alguma dessas frases já tirou seu sono?
                        </h2>
                        <div className="pains" style={{ textAlign: 'left' }}>
                            <div className="pain">
                                <q>Ele chora todo dia na porta da escola.</q>E
                                você sai com o coração apertado.
                            </div>
                            <div className="pain">
                                <q>Ninguém quer brincar comigo.</q>E você não
                                sabe se deve intervir.
                            </div>
                            <div className="pain">
                                <q>Só mais cinco minutinhos de tela…</q>E as
                                telas tomaram conta da rotina.
                            </div>
                            <div className="pain">
                                <q>Será que eu deveria estar mais presente?</q>A
                                culpa de trabalhar pesa no fim do dia.
                            </div>
                        </div>
                        <p className="sub">
                            Essas situações são comuns, e todas têm caminho. O{' '}
                            <b>Crescer Seguro</b> mostra o que fazer em cada
                            uma, de forma direta e sem julgamento.
                        </p>
                    </div>
                </section>

                <section className="method">
                    <div className="wrap">
                        <div
                            className="eyebrow"
                            style={{ color: 'var(--gold)' } as CSSProperties}
                        >
                            Como o guia funciona
                        </div>
                        <h2
                            style={{
                                fontSize: 'clamp(28px,4vw,40px)',
                                marginTop: '10px',
                            }}
                        >
                            Cada pergunta, uma página. Cada página, 4 partes.
                        </h2>
                        <div className="steps4">
                            <div
                                className="st"
                                style={
                                    { '--c': 'var(--orange)' } as CSSProperties
                                }
                            >
                                <b>Entenda</b>
                                <span>
                                    O que está por trás do comportamento do seu
                                    filho.
                                </span>
                            </div>
                            <div
                                className="st"
                                style={
                                    { '--c': 'var(--teal)' } as CSSProperties
                                }
                            >
                                <b>O que fazer</b>
                                <span>
                                    Quatro passos simples para aplicar hoje
                                    mesmo.
                                </span>
                            </div>
                            <div
                                className="st"
                                style={
                                    { '--c': 'var(--coral)' } as CSSProperties
                                }
                            >
                                <b>Evite</b>
                                <span>
                                    As atitudes comuns que pioram a situação.
                                </span>
                            </div>
                            <div
                                className="st"
                                style={
                                    { '--c': 'var(--violet)' } as CSSProperties
                                }
                            >
                                <b>Frase que ajuda</b>
                                <span>
                                    Palavras prontas para dizer ao seu filho.
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="wrap">
                        <div className="center">
                            <div className="eyebrow">
                                O que você vai encontrar
                            </div>
                            <h2
                                style={{
                                    fontSize: 'clamp(28px,4vw,40px)',
                                    marginTop: '10px',
                                }}
                            >
                                4 capítulos, 16 respostas práticas
                            </h2>
                        </div>
                        <div className="chaps">
                            <div
                                className="chap"
                                style={
                                    {
                                        '--c': 'var(--orange)',
                                        '--bg': '#FFF3E8',
                                    } as CSSProperties
                                }
                            >
                                <div className="tag">Capítulo 1</div>
                                <h3>Adaptação & Rotina Escolar</h3>
                                <ul>
                                    <li>
                                        Como ajudar o filho a não chorar na
                                        entrada da escola
                                    </li>
                                    <li>
                                        O que fazer quando ele não quer ir de
                                        jeito nenhum
                                    </li>
                                    <li>O papel dos pais na adaptação</li>
                                    <li>
                                        Acompanhar a rotina sem ser invasivo
                                    </li>
                                </ul>
                            </div>
                            <div
                                className="chap"
                                style={
                                    {
                                        '--c': 'var(--teal)',
                                        '--bg': '#E8F6F3',
                                    } as CSSProperties
                                }
                            >
                                <div className="tag">Capítulo 2</div>
                                <h3>Proteção & Segurança</h3>
                                <ul>
                                    <li>
                                        Proteger seu filho dos perigos da
                                        internet
                                    </li>
                                    <li>
                                        Sinais de que algo não vai bem na escola
                                    </li>
                                    <li>Ensinar a se defender sem violência</li>
                                    <li>Saúde mental e excesso de telas</li>
                                </ul>
                            </div>
                            <div
                                className="chap"
                                style={
                                    {
                                        '--c': 'var(--violet)',
                                        '--bg': '#F1EDFB',
                                    } as CSSProperties
                                }
                            >
                                <div className="tag">Capítulo 3</div>
                                <h3>Amizades & Socialização</h3>
                                <ul>
                                    <li>
                                        Ajudar o filho tímido a fazer amigos
                                    </li>
                                    <li>
                                        Quando ele diz que ninguém quer brincar
                                    </li>
                                    <li>
                                        Brigas entre crianças e entre os pais
                                    </li>
                                    <li>Brincar sozinho: quando é normal</li>
                                </ul>
                            </div>
                            <div
                                className="chap"
                                style={
                                    {
                                        '--c': 'var(--coral)',
                                        '--bg': '#FDECEF',
                                    } as CSSProperties
                                }
                            >
                                <div className="tag">Capítulo 4</div>
                                <h3>Medos, Anseios & Futuro</h3>
                                <ul>
                                    <li>
                                        Ansiedade de separação nos filhos e nos
                                        pais
                                    </li>
                                    <li>O medo da violência escolar</li>
                                    <li>A culpa da mãe que trabalha</li>
                                    <li>Criar filhos resilientes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="inside">
                    <div className="wrap center">
                        <div className="eyebrow">Veja por dentro</div>
                        <h2
                            style={{
                                fontSize: 'clamp(28px,4vw,40px)',
                                marginTop: '10px',
                            }}
                        >
                            Bonito de ler, fácil de aplicar
                        </h2>
                        <div className="shots">
                            <figure>
                                <img
                                    src={schoolPreview}
                                    alt="Página da pergunta sobre choro na entrada da escola"
                                    loading="lazy"
                                />
                                <figcaption>Uma pergunta por página</figcaption>
                            </figure>
                            <figure>
                                <img
                                    src={safetyPreview}
                                    alt="Página sobre sinais de mal-estar na escola"
                                    loading="lazy"
                                />
                                <figcaption>
                                    Passos claros e frases prontas
                                </figcaption>
                            </figure>
                            <figure>
                                <img
                                    src={weeklyChecklist}
                                    alt="Checklist semanal dos pais"
                                    loading="lazy"
                                />
                                <figcaption>
                                    Checklist semanal para imprimir
                                </figcaption>
                            </figure>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="wrap two">
                        <div>
                            <h2 style={{ fontSize: 'clamp(26px,3.6vw,36px)' }}>
                                Este guia é para você se…
                            </h2>
                            <ul className="check">
                                <li>
                                    Seu filho está na educação infantil ou nos
                                    primeiros anos do ensino fundamental
                                </li>
                                <li>
                                    Você quer respostas práticas, sem textos
                                    longos e teóricos
                                </li>
                                <li>
                                    Você quer saber o que dizer, e não só o que
                                    pensar
                                </li>
                                <li>
                                    Você tem pouco tempo e quer ler só o que
                                    precisa agora
                                </li>
                            </ul>
                        </div>
                        <div className="note">
                            <b>Importante:</b> o Crescer Seguro é um material
                            educativo. Ele não substitui o acompanhamento de
                            pediatras, psicólogos e da escola. Se os sinais de
                            sofrimento do seu filho persistirem, procure um
                            profissional.
                        </div>
                    </div>
                </section>

                <section className="final" id="oferta">
                    <div className="wrap center">
                        <div className="eyebrow">Oferta de lançamento</div>
                        <h2
                            style={{
                                fontSize: 'clamp(28px,4vw,40px)',
                                marginTop: '10px',
                            }}
                        >
                            Comece hoje a ter conversas mais tranquilas com seu
                            filho
                        </h2>
                        <div className="offer-card">
                            <ul>
                                <li>
                                    <span>
                                        E-book Crescer Seguro (25 páginas)
                                    </span>
                                    <span>PDF</span>
                                </li>
                                <li>
                                    <span>16 perguntas com passo a passo</span>
                                    <span>✓</span>
                                </li>
                                <li>
                                    <span>16 frases prontas para usar</span>
                                    <span>✓</span>
                                </li>
                                <li>
                                    <span>Checklist semanal dos pais</span>
                                    <span>✓</span>
                                </li>
                            </ul>
                            <div className="big-price">
                                <s>De R$ 37 por</s>
                                <b>R$ 19,90</b>
                            </div>
                            <p className="small">
                                Pagamento único · Pix, cartão ou boleto
                            </p>
                            <a
                                className="btn"
                                href="https://pay.kiwify.com.br/b1cEJF3"
                            >
                                Quero o Crescer Seguro
                            </a>
                            <div className="secure">
                                <span>🔒 Compra segura</span>
                                <span>Acesso imediato por e-mail</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="wrap">
                        <div className="guar">
                            <div className="seal">
                                <b>7</b>
                                <span>
                                    dias de
                                    <br />
                                    garantia
                                </span>
                            </div>
                            <div>
                                <h3>Garantia incondicional de 7 dias</h3>
                                <p>
                                    Leia o guia com calma. Se não for útil para
                                    a sua família, peça o reembolso dentro de 7
                                    dias e devolvemos 100% do valor, sem
                                    perguntas.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section style={{ paddingTop: '0' }}>
                    <div className="wrap">
                        <h2
                            className="center"
                            style={{ fontSize: 'clamp(26px,3.6vw,36px)' }}
                        >
                            Perguntas frequentes
                        </h2>
                        <div className="faq">
                            <details>
                                <summary>Como recebo o e-book?</summary>
                                <p>
                                    Logo após a confirmação do pagamento, você
                                    recebe o link de acesso no e-mail cadastrado
                                    na compra.
                                </p>
                            </details>
                            <details>
                                <summary>Posso ler no celular?</summary>
                                <p>
                                    Sim. O guia é um PDF que abre no celular, no
                                    tablet ou no computador. Você também pode
                                    imprimir.
                                </p>
                            </details>
                            <details>
                                <summary>
                                    Para qual idade o guia é indicado?
                                </summary>
                                <p>
                                    Para pais de crianças na educação infantil e
                                    nos primeiros anos do ensino fundamental,
                                    mais ou menos dos 2 aos 10 anos.
                                </p>
                            </details>
                            <details>
                                <summary>
                                    O pagamento por Pix libera na hora?
                                </summary>
                                <p>
                                    Sim. Pix e cartão são aprovados em poucos
                                    minutos. O boleto pode levar até 3 dias
                                    úteis para compensar.
                                </p>
                            </details>
                            <details>
                                <summary>E se eu não gostar?</summary>
                                <p>
                                    Você tem 7 dias de garantia. Basta pedir o
                                    reembolso dentro desse prazo para receber
                                    100% do valor de volta.
                                </p>
                            </details>
                        </div>
                        <div className="center" style={{ marginTop: '40px' }}>
                            <a
                                className="btn"
                                href="https://pay.kiwify.com.br/b1cEJF3"
                            >
                                Quero o guia por R$ 19,90
                            </a>
                        </div>
                    </div>
                </section>

                <footer>
                    <div className="wrap">
                        Crescer Seguro · O guia dos pais para escola, amizades e
                        emoções
                        <br />
                        Este produto não substitui o acompanhamento de
                        profissionais de saúde e educação.
                    </div>
                </footer>
            </main>
        </>
    );
}
