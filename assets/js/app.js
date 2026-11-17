(() => {
    'use strict';

    /* ------------------------------------------------------------
       Tema claro / escuro
       ------------------------------------------------------------ */
    const temaRaiz = document.documentElement;
    const botoesTema = document.querySelectorAll('[data-theme-toggle]');
    const metaTema = document.querySelector('meta[name="theme-color"]');
    const CHAVE_TEMA = 'meridiano-theme';

    const temaAtual = () => temaRaiz.dataset.theme === 'dark' ? 'dark' : 'light';

    const aplicarTema = (tema, persistir = false) => {
        const escuro = tema === 'dark';
        temaRaiz.classList.add('is-theme-switching');
        temaRaiz.dataset.theme = tema;

        if (persistir) {
            try {
                localStorage.setItem(CHAVE_TEMA, tema);
            } catch (erro) {
                /* A preferência continua válida apenas para esta página. */
            }
        }

        if (metaTema) {
            metaTema.setAttribute('content', escuro ? '#0b1715' : '#0f2725');
        }

        botoesTema.forEach((botao) => {
            botao.setAttribute('aria-pressed', String(escuro));
            botao.setAttribute('aria-label', escuro ? 'Ativar modo claro' : 'Ativar modo escuro');
            botao.dataset.tooltip = escuro ? 'Ativar modo claro' : 'Ativar modo escuro';
        });

        const tooltip = document.getElementById('ui-tooltip');
        if (tooltip && tooltip.classList.contains('is-visible')) {
            tooltip.textContent = escuro ? 'Ativar modo claro' : 'Ativar modo escuro';
        }

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => temaRaiz.classList.remove('is-theme-switching'));
        });
    };

    aplicarTema(temaAtual());

    botoesTema.forEach((botao) => {
        botao.addEventListener('click', () => {
            aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark', true);
        });
    });

    /* ------------------------------------------------------------
       Navegação mobile
       ------------------------------------------------------------ */
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.querySelector('[data-sidebar-close]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

    const setSidebar = (open) => {
        if (!sidebar || !sidebarToggle) return;

        sidebar.classList.toggle('is-open', open);
        sidebarToggle.setAttribute('aria-expanded', String(open));

        if (backdrop) {
            backdrop.hidden = !open;
        }

        document.body.style.overflow = open && window.innerWidth <= 1060 ? 'hidden' : '';

        if (open) {
            const firstLink = sidebar.querySelector('a, button');
            if (firstLink) firstLink.focus({ preventScroll: true });
        } else {
            sidebarToggle.focus({ preventScroll: true });
        }
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebar(!sidebar.classList.contains('is-open'));
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', () => setSidebar(false));
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1060 && sidebar && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            if (backdrop) backdrop.hidden = true;
            document.body.style.overflow = '';
            if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    });

    /* ------------------------------------------------------------
       Mostrar / ocultar senha
       ------------------------------------------------------------ */
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const wrap = button.closest('.input-wrap');
            const input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
        });
    });

    /* ------------------------------------------------------------
       Mensagens dispensáveis
       ------------------------------------------------------------ */
    document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('.flash');
            const stack = flash ? flash.parentElement : null;
            if (flash) flash.remove();
            if (stack && stack.children.length === 0) stack.remove();
        });
    });

    /* ------------------------------------------------------------
       Confirmação de ações destrutivas
       ------------------------------------------------------------ */
    const confirmDialog = document.getElementById('confirm-dialog');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmAccept = confirmDialog ? confirmDialog.querySelector('[data-confirm-accept]') : null;
    const confirmCancel = confirmDialog ? confirmDialog.querySelector('[data-confirm-cancel]') : null;
    let pendingForm = null;

    const openConfirmation = (form, message, label) => {
        if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
            if (window.confirm(message)) {
                form.dataset.confirmed = '1';
                form.requestSubmit();
            }
            return;
        }

        pendingForm = form;
        if (confirmMessage) confirmMessage.textContent = message;
        if (confirmAccept) confirmAccept.textContent = label || 'Excluir';
        confirmDialog.showModal();
        if (confirmCancel) confirmCancel.focus();
    };

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            openConfirmation(
                form,
                form.dataset.confirm || 'Esta ação não pode ser desfeita.',
                form.dataset.confirmLabel
            );
        });
    });

    if (confirmAccept) {
        confirmAccept.addEventListener('click', () => {
            if (!pendingForm || !confirmDialog) return;

            pendingForm.dataset.confirmed = '1';
            confirmDialog.close();
            pendingForm.requestSubmit();
            pendingForm = null;
        });
    }

    if (confirmCancel) {
        confirmCancel.addEventListener('click', () => {
            pendingForm = null;
            if (confirmDialog) confirmDialog.close();
        });
    }

    if (confirmDialog) {
        confirmDialog.addEventListener('click', (event) => {
            if (event.target !== confirmDialog) return;
            const rect = confirmDialog.getBoundingClientRect();
            const outside = event.clientX < rect.left
                || event.clientX > rect.right
                || event.clientY < rect.top
                || event.clientY > rect.bottom;

            if (outside) {
                pendingForm = null;
                confirmDialog.close();
            }
        });

        confirmDialog.addEventListener('cancel', () => {
            pendingForm = null;
        });
    }

    /* ------------------------------------------------------------
       Reserva: cálculo do valor a partir do pacote
       ------------------------------------------------------------ */
    const reservaForm = document.querySelector('[data-reserva-form]');
    if (reservaForm) {
        const viagem = reservaForm.querySelector('[data-viagem-select]');
        const viajantes = reservaForm.querySelector('[data-viajantes]');
        const valor = reservaForm.querySelector('[data-valor-total]');
        const preview = reservaForm.querySelector('[data-reserva-preview]');
        const autoCalc = reservaForm.dataset.autocalc === 'true';

        const precoSelecionado = () => {
            if (!viagem || viagem.selectedIndex < 0) return 0;
            const option = viagem.options[viagem.selectedIndex];
            return parseFloat(option.dataset.preco || '0');
        };

        const atualizar = (forcar) => {
            if (!valor) return;

            const preco = precoSelecionado();
            const quantidade = Math.max(1, parseInt(viajantes ? viajantes.value : '1', 10) || 1);
            const total = preco * quantidade;

            if (preco > 0) {
                if (forcar || valor.value === '' || valor.value === '0.00' || valor.value === '0') {
                    valor.value = total.toFixed(2);
                }

                if (preview) {
                    preview.textContent = `${quantidade} ${quantidade === 1 ? 'viajante' : 'viajantes'} × R$ ${preco.toFixed(2).replace('.', ',')} = R$ ${total.toFixed(2).replace('.', ',')}`;
                }
            } else if (preview) {
                preview.textContent = 'Selecione uma partida para calcular o valor.';
            }
        };

        if (autoCalc) {
            atualizar(true);
        }

        if (viagem && viajantes) {
            viagem.addEventListener('change', () => atualizar(autoCalc));
            viajantes.addEventListener('input', () => atualizar(autoCalc));
        }

        const recalcular = reservaForm.querySelector('[data-recalcular]');
        if (recalcular) {
            recalcular.addEventListener('click', () => {
                if (valor) valor.value = '';
                atualizar(true);
                if (valor) valor.focus();
            });
        }
    }

    /* ------------------------------------------------------------
       Pagamento: preencher saldo restante
       ------------------------------------------------------------ */
    const pagamentoForm = document.querySelector('[data-pagamento-form]');
    if (pagamentoForm) {
        const botaoSaldo = pagamentoForm.querySelector('[data-usar-saldo]');
        const campoValor = pagamentoForm.querySelector('[data-pagamento-valor]');
        const reservaSelect = pagamentoForm.querySelector('[data-reserva-pagamento-select]');

        const saldoSelecionado = () => {
            if (!botaoSaldo) return 0;

            let saldo = parseFloat(botaoSaldo.dataset.usarSaldo || '0');
            if (reservaSelect && reservaSelect.selectedIndex >= 0) {
                const option = reservaSelect.options[reservaSelect.selectedIndex];
                const saldoOpcao = parseFloat(option.dataset.saldo || '');
                if (!Number.isNaN(saldoOpcao)) saldo = saldoOpcao;
            }

            return saldo;
        };

        if (botaoSaldo && campoValor) {
            botaoSaldo.addEventListener('click', () => {
                const saldo = saldoSelecionado();
                if (saldo > 0) {
                    campoValor.value = saldo.toFixed(2);
                    campoValor.focus();
                }
            });
        }

        const status = pagamentoForm.querySelector('[data-pagamento-status]');
        const dataPagamento = pagamentoForm.querySelector('[data-data-pagamento]');
        const vencimento = pagamentoForm.querySelector('[data-vencimento]');

        const atualizarObrigatorios = () => {
            if (!status) return;
            if (dataPagamento) dataPagamento.required = status.value === 'pago';
            if (vencimento) vencimento.required = status.value === 'pendente';
        };

        if (status) {
            atualizarObrigatorios();
            status.addEventListener('change', () => {
                if (status.value === 'pago' && dataPagamento && !dataPagamento.value) {
                    dataPagamento.value = new Date().toISOString().slice(0, 10);
                }
                if (status.value === 'pendente' && vencimento && !vencimento.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 7);
                    vencimento.value = d.toISOString().slice(0, 10);
                }
                atualizarObrigatorios();
            });
        }
    }

    /* ------------------------------------------------------------
       Máscaras de CPF e telefone
       ------------------------------------------------------------ */
    const aplicarMascaraCpf = (valor) => {
        const n = valor.replace(/\D/g, '').slice(0, 11);
        return n
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    };

    const aplicarMascaraTelefone = (valor) => {
        const n = valor.replace(/\D/g, '').slice(0, 11);
        if (n.length <= 10) {
            return n.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
        }
        return n.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
    };

    document.querySelectorAll('[data-mask="cpf"]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = aplicarMascaraCpf(input.value);
        });
    });

    document.querySelectorAll('[data-mask="telefone"]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = aplicarMascaraTelefone(input.value);
        });
    });

    /* ------------------------------------------------------------
       Datas: a volta nunca antes da ida
       ------------------------------------------------------------ */
    document.querySelectorAll('[data-sync-dates]').forEach((form) => {
        const ida = form.querySelector('[data-data-ida]');
        const volta = form.querySelector('[data-data-volta]');
        if (!ida || !volta) return;

        const sync = () => {
            if (ida.value) {
                volta.min = ida.value;
                if (volta.value && volta.value < ida.value) volta.value = ida.value;
            }
        };

        ida.addEventListener('change', sync);
        sync();
    });

    /* ------------------------------------------------------------
       Estado de envio dos formulários
       ------------------------------------------------------------ */
    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (!form.checkValidity()) return;

            const button = form.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;

            button.disabled = true;
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');

            const label = button.querySelector('[data-button-label]');
            if (label) label.textContent = 'Salvando…';
        });
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        document.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.classList.remove('is-loading');
        });
    });

    /* ------------------------------------------------------------
       Impressão de comprovantes
       ------------------------------------------------------------ */
    document.querySelectorAll('[data-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });

    /* ------------------------------------------------------------
       Tooltip para ações somente com ícone
       ------------------------------------------------------------ */
    const tooltip = document.createElement('div');
    tooltip.className = 'ui-tooltip';
    tooltip.id = 'ui-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);

    const rotuloTooltip = (elemento) => (elemento.dataset.tooltip || elemento.getAttribute('title') || '').trim();

    document.querySelectorAll('.action-link[title], .icon-button[title], [data-tooltip]').forEach((elemento) => {
        const rotulo = rotuloTooltip(elemento);
        if (!rotulo) return;
        elemento.dataset.tooltip = rotulo;
        elemento.removeAttribute('title');
    });

    let alvoTooltip = null;
    let timerTooltip = 0;
    let tooltipVisivel = false;

    const posicionarTooltip = (elemento) => {
        const area = elemento.getBoundingClientRect();
        const largura = tooltip.offsetWidth;
        const altura = tooltip.offsetHeight;
        let topo = area.top - altura - 9;
        let abaixo = false;

        if (topo < 8) {
            topo = area.bottom + 9;
            abaixo = true;
        }

        const metade = largura / 2;
        const centro = Math.min(
            Math.max(area.left + area.width / 2, metade + 8),
            window.innerWidth - metade - 8
        );

        tooltip.style.left = centro + 'px';
        tooltip.style.top = topo + 'px';
        tooltip.classList.toggle('is-below', abaixo);
    };

    const esconderTooltip = () => {
        window.clearTimeout(timerTooltip);
        if (alvoTooltip) alvoTooltip.removeAttribute('aria-describedby');
        alvoTooltip = null;
        tooltipVisivel = false;
        tooltip.classList.remove('is-visible');
    };

    const mostrarTooltip = (elemento) => {
        const rotulo = rotuloTooltip(elemento);
        if (!rotulo) return;

        window.clearTimeout(timerTooltip);
        alvoTooltip = elemento;
        tooltip.textContent = rotulo;
        tooltip.classList.add('is-visible');
        tooltipVisivel = true;
        posicionarTooltip(elemento);
        elemento.setAttribute('aria-describedby', 'ui-tooltip');
    };

    document.addEventListener('pointerover', (event) => {
        const elemento = event.target.closest('[data-tooltip]');
        if (!elemento || elemento === alvoTooltip) return;

        esconderTooltip();
        timerTooltip = window.setTimeout(() => mostrarTooltip(elemento), 260);
    });

    document.addEventListener('pointerout', (event) => {
        const elemento = event.target.closest('[data-tooltip]');
        if (!elemento) return;
        if (event.relatedTarget && elemento.contains(event.relatedTarget)) return;
        esconderTooltip();
    });

    document.addEventListener('focusin', (event) => {
        const elemento = event.target.closest('[data-tooltip]');
        if (!elemento) return;
        mostrarTooltip(elemento);
    });

    document.addEventListener('focusout', () => {
        if (tooltipVisivel) esconderTooltip();
    });

    window.addEventListener('scroll', esconderTooltip, { passive: true });
    window.addEventListener('resize', esconderTooltip);
})();
