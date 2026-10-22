/**
 * Motor Interativo do Timer de Provas
 * Contagem Regressiva de Alta Precisão, Sintetizador de Áudio (Web Audio API),
 * Alertas em Tela, Voz e Modo Telão Fullscreen
 */

class ExamTimer {
    constructor(config) {
        this.provaId = config.provaId;
        this.tempoTotal = parseInt(config.tempoTotal, 10);
        this.segundosRestantes = parseInt(config.segundosRestantes, 10);
        this.intervaloAlerta = parseInt(config.intervaloAlerta, 10) || 1800; // a cada 30 min
        this.alertaSom = Boolean(config.alertaSom);
        this.tipoSom = config.tipoSom || 'beep';
        this.alertaAvisoTela = Boolean(config.alertaAvisoTela);
        this.alertaVoz = Boolean(config.alertaVoz);
        this.status = config.status || 'configurada'; // 'configurada', 'em_andamento', 'pausada', 'concluida'

        this.timerInterval = null;
        this.alertasDisparados = new Set();
        this.audioCtx = null;

        this.dom = {
            horas: document.getElementById('timer-horas'),
            minutos: document.getElementById('timer-minutos'),
            segundos: document.getElementById('timer-segundos'),
            statusBadge: document.getElementById('timer-status-badge'),
            progressBar: document.getElementById('timer-progress-bar'),
            progressText: document.getElementById('timer-progress-text'),
            digitsContainer: document.getElementById('timer-digits-container'),
            btnIniciar: document.getElementById('btn-timer-iniciar'),
            btnPausar: document.getElementById('btn-timer-pausar'),
            btnRetomar: document.getElementById('btn-timer-retomar'),
            btnConcluir: document.getElementById('btn-timer-concluir'),
            btnReiniciar: document.getElementById('btn-timer-reiniciar'),
            btnFullscreen: document.getElementById('btn-timer-fullscreen'),
            container: document.getElementById('timer-main-container')
        };

        this.inicializarEventos();
        this.renderizar();

        // Se já estava em andamento no banco de dados, continua
        if (this.status === 'em_andamento') {
            this.iniciarContagem(false);
        }
    }

    inicializarEventos() {
        if (this.dom.btnIniciar) {
            this.dom.btnIniciar.addEventListener('click', () => this.iniciarContagem(true));
        }
        if (this.dom.btnPausar) {
            this.dom.btnPausar.addEventListener('click', () => this.pausarContagem());
        }
        if (this.dom.btnRetomar) {
            this.dom.btnRetomar.addEventListener('click', () => this.retomarContagem());
        }
        if (this.dom.btnReiniciar) {
            this.dom.btnReiniciar.addEventListener('click', () => this.confirmarReiniciar());
        }
        if (this.dom.btnConcluir) {
            this.dom.btnConcluir.addEventListener('click', () => this.confirmarConclusao());
        }
        if (this.dom.btnFullscreen) {
            this.dom.btnFullscreen.addEventListener('click', () => this.alternarFullscreen());
        }

        // Botões de acréscimo rápido de tempo (+5min, +10min)
        document.querySelectorAll('.btn-acrescimo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const minutos = parseInt(e.currentTarget.getAttribute('data-minutos') || '5', 10);
                this.adicionarTempoExtra(minutos);
            });
        });

        // Testar Som
        const btnTestarSom = document.getElementById('btn-testar-som');
        if (btnTestarSom) {
            btnTestarSom.addEventListener('click', () => {
                this.emitirAlertaSonoro(this.tipoSom);
                mostrarToast('info', `Som de teste (${this.tipoSom}) reproduzido.`);
            });
        }
    }

    getAudioContext() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        }
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
        return this.audioCtx;
    }

    /**
     * Sintetizador de Som Web Audio API (100% nativo e sem dependência externa)
     */
    emitirAlertaSonoro(tipo = 'beep') {
        try {
            const ctx = this.getAudioContext();
            const now = ctx.currentTime;

            if (tipo === 'sino') {
                // Toque de sino suave e harmônico
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, now); // Ré5
                osc.frequency.exponentialRampToValueAtTime(880, now + 0.15); // Lá5
                gain.gain.setValueAtTime(0.6, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 1.6);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 1.6);
            } else if (tipo === 'alarme') {
                // Alarme com 3 pulsos para reta final
                for (let i = 0; i < 3; i++) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(880, now + (i * 0.25));
                    gain.gain.setValueAtTime(0.3, now + (i * 0.25));
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (i * 0.25) + 0.18);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + (i * 0.25));
                    osc.stop(now + (i * 0.25) + 0.2);
                }
            } else {
                // Beep padrão duplo
                for (let i = 0; i < 2; i++) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(750, now + (i * 0.18));
                    gain.gain.setValueAtTime(0.5, now + (i * 0.18));
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (i * 0.18) + 0.12);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + (i * 0.18));
                    osc.stop(now + (i * 0.18) + 0.15);
                }
            }
        } catch (e) {
            console.warn("Áudio não disponível ou bloqueado pelo navegador:", e);
        }
    }

    /**
     * Leitura por Voz Sintetizada (Web Speech API)
     */
    emitirAvisoVoz(texto) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(texto);
            utter.lang = 'pt-BR';
            utter.rate = 0.95;
            window.speechSynthesis.speak(utter);
        }
    }

    iniciarContagem(comNotificacao = true) {
        this.status = 'em_andamento';
        this.atualizarBotoes();
        this.getAudioContext(); // desbloqueia contexto com gesto do usuário

        this.pararIntervalo();
        this.timerInterval = setInterval(() => {
            this.tick();
        }, 1000);

        if (comNotificacao) {
            this.enviarAcaoBackend('iniciar', this.segundosRestantes);
            mostrarToast('success', 'Prova iniciada! Contagem em andamento.');
        }
    }

    pausarContagem() {
        this.status = 'pausada';
        this.pararIntervalo();
        this.atualizarBotoes();
        this.renderizar();
        this.enviarAcaoBackend('pausar', this.segundosRestantes);
        mostrarToast('warning', 'Timer pausado pelo coordenador.');
    }

    retomarContagem() {
        this.status = 'em_andamento';
        this.atualizarBotoes();
        this.pararIntervalo();
        this.timerInterval = setInterval(() => {
            this.tick();
        }, 1000);
        this.enviarAcaoBackend('retomar', this.segundosRestantes);
        mostrarToast('success', 'Contagem retomada com sucesso.');
    }

    pararIntervalo() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    tick() {
        if (this.segundosRestantes > 0) {
            this.segundosRestantes--;
            this.verificarAlertas();
            this.renderizar();

            // Sincroniza periodicamente com o backend a cada 60s
            if (this.segundosRestantes % 60 === 0) {
                this.enviarAcaoBackend('sincronizar', this.segundosRestantes);
            }
        } else {
            // Tempo esgotado!
            this.pararIntervalo();
            this.status = 'concluida';
            this.renderizar();
            this.atualizarBotoes();
            this.dispararFimDeProva();
        }
    }

    /**
     * Verifica se o tempo atual coincide com gatilhos de alerta configurados
     */
    verificarAlertas() {
        const seg = this.segundosRestantes;
        const chaveAlerta = `alerta_${seg}`;

        if (this.alertasDisparados.has(chaveAlerta)) return;

        let deveAlertar = false;
        let mensagemAlerta = "";
        let tituloAlerta = "Aviso de Tempo";

        // 1. Alerta por Intervalo configurado (ex: a cada 30 min)
        if (this.intervaloAlerta > 0 && seg > 0 && (this.tempoTotal - seg) % this.intervaloAlerta === 0) {
            deveAlertar = true;
            const minRestantes = Math.round(seg / 60);
            mensagemAlerta = `Atenção: Restam ${minRestantes} minutos para o término da prova.`;
            tituloAlerta = `Alerta Periódico (${minRestantes} min restantes)`;
        }

        // 2. Marcas Críticas de Reta Final: 30min, 15min, 5min, 1min
        const marcasCriticas = [
            { segs: 1800, msg: "Atenção: Restam 30 minutos de prova! Comecem a revisar suas respostas." },
            { segs: 900,  msg: "Atenção: Restam 15 minutos! Preencham a folha de respostas definitiva." },
            { segs: 300,  msg: "Atenção: Últimos 5 minutos de prova! Guardem os rascunhos e confirmem os dados." },
            { segs: 60,   msg: "Atenção: Último minuto! Concluam a entrega da prova imediatamente." }
        ];

        for (const m of marcasCriticas) {
            if (seg === m.segs) {
                deveAlertar = true;
                mensagemAlerta = m.msg;
                tituloAlerta = `Reta Final • ${m.segs / 60} min restantes`;
                break;
            }
        }

        if (deveAlertar) {
            this.alertasDisparados.add(chaveAlerta);
            this.executarAlerta(tituloAlerta, mensagemAlerta);
        }
    }

    executarAlerta(titulo, mensagem) {
        // Som
        if (this.alertaSom) {
            this.emitirAlertaSonoro(this.tipoSom);
        }

        // Aviso em Tela
        if (this.alertaAvisoTela) {
            abrirAlertaSala(titulo, mensagem);
        } else {
            mostrarToast('warning', mensagem, titulo);
        }

        // Voz Sintetizada
        if (this.alertaVoz) {
            this.emitirAvisoVoz(mensagem);
        }

        // Registra no Log de Auditoria do backend
        this.enviarAcaoBackend('alerta_disparado', this.segundosRestantes, mensagem);
    }

    dispararFimDeProva() {
        this.emitirAlertaSonoro('alarme');

        const msgFim = "Tempo de prova esgotado! Todas as canetas devem ser baixadas e as provas entregues ao fiscal.";
        abrirAlertaSala("TEMPO ESGOTADO!", msgFim);

        if (this.alertaVoz) {
            this.emitirAvisoVoz("Atenção: tempo de prova encerrado!");
        }

        this.enviarAcaoBackend('concluir', 0, "Tempo limite atingido.");
    }

    adicionarTempoExtra(minutos) {
        confirmarAcao(
            `Deseja conceder acréscimo de <strong>+${minutos} minutos</strong> à prova? Esta concessão será auditada.`,
            () => {
                const segExtra = minutos * 60;
                this.segundosRestantes += segExtra;
                this.renderizar();
                this.enviarAcaoBackend('acrescimo', this.segundosRestantes, minutos);
                mostrarToast('success', `+${minutos} minutos adicionados à contagem.`);
            },
            'Conceder Tempo Extra',
            'warning'
        );
    }

    confirmarReiniciar() {
        confirmarAcao(
            'Tem certeza que deseja reiniciar o timer para o tempo total original? A contagem atual será cancelada.',
            () => {
                this.pararIntervalo();
                this.status = 'configurada';
                this.segundosRestantes = this.tempoTotal;
                this.alertasDisparados.clear();
                this.renderizar();
                this.atualizarBotoes();
                this.enviarAcaoBackend('reiniciar', this.tempoTotal);
                mostrarToast('info', 'Timer reiniciado com sucesso.');
            },
            'Reiniciar Timer',
            'danger'
        );
    }

    confirmarConclusao() {
        confirmarAcao(
            'Deseja encerrar e finalizar oficialmente esta prova agora?',
            () => {
                this.pararIntervalo();
                this.status = 'concluida';
                this.renderizar();
                this.atualizarBotoes();
                this.enviarAcaoBackend('concluir', this.segundosRestantes, 'Encerrada manualmente pelo coordenador.');
                mostrarToast('success', 'Prova finalizada e arquivada.');
            },
            'Finalizar Prova',
            'success'
        );
    }

    alternarFullscreen() {
        const elem = this.dom.container;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }

    renderizar() {
        const seg = Math.max(0, this.segundosRestantes);
        const horas = Math.floor(seg / 3600);
        const minutos = Math.floor((seg % 3600) / 60);
        const segundos = seg % 60;

        if (this.dom.horas) this.dom.horas.textContent = String(horas).padStart(2, '0');
        if (this.dom.minutos) this.dom.minutos.textContent = String(minutos).padStart(2, '0');
        if (this.dom.segundos) this.dom.segundos.textContent = String(segundos).padStart(2, '0');

        // Percentual decorrido
        const percentual = this.tempoTotal > 0 ? Math.min(100, Math.round(((this.tempoTotal - seg) / this.tempoTotal) * 100)) : 0;
        if (this.dom.progressBar) {
            this.dom.progressBar.style.width = `${percentual}%`;
        }
        if (this.dom.progressText) {
            this.dom.progressText.textContent = `${percentual}% do tempo decorrido`;
        }

        // Estilos de cor conforme o tempo restante
        if (this.dom.digitsContainer) {
            this.dom.digitsContainer.className = 'font-timer text-5xl sm:text-7xl md:text-8xl lg:text-9xl font-black tracking-wider flex items-center justify-center select-none ';

            if (this.status === 'concluida' || seg === 0) {
                this.dom.digitsContainer.classList.add('text-rose-500', 'digits-glow-red');
            } else if (seg <= 300) {
                // Últimos 5 minutos: vermelho pulsante
                this.dom.digitsContainer.classList.add('text-rose-400', 'digits-glow-red');
            } else if (seg <= 900) {
                // Últimos 15 minutos: âmbar/laranja
                this.dom.digitsContainer.classList.add('text-amber-400', 'digits-glow-amber');
            } else {
                // Tempo normal: verde ou azul
                this.dom.digitsContainer.classList.add('text-sky-400', 'digits-glow-sky');
            }
        }

        // Status Badge
        if (this.dom.statusBadge) {
            if (this.status === 'em_andamento') {
                this.dom.statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                this.dom.statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span> Prova em Andamento';
            } else if (this.status === 'pausada') {
                this.dom.statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30';
                this.dom.statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400 mr-2"></span> Timer Pausado';
            } else if (this.status === 'concluida') {
                this.dom.statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30';
                this.dom.statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-400 mr-2"></span> Prova Encerrada';
            } else {
                this.dom.statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700';
                this.dom.statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span> Pronto para Iniciar';
            }
        }
    }

    atualizarBotoes() {
        if (!this.dom.btnIniciar) return;

        if (this.status === 'em_andamento') {
            this.dom.btnIniciar.classList.add('hidden');
            if (this.dom.btnPausar) this.dom.btnPausar.classList.remove('hidden');
            if (this.dom.btnRetomar) this.dom.btnRetomar.classList.add('hidden');
        } else if (this.status === 'pausada') {
            this.dom.btnIniciar.classList.add('hidden');
            if (this.dom.btnPausar) this.dom.btnPausar.classList.add('hidden');
            if (this.dom.btnRetomar) this.dom.btnRetomar.classList.remove('hidden');
        } else if (this.status === 'concluida') {
            this.dom.btnIniciar.classList.add('hidden');
            if (this.dom.btnPausar) this.dom.btnPausar.classList.add('hidden');
            if (this.dom.btnRetomar) this.dom.btnRetomar.classList.add('hidden');
        } else {
            this.dom.btnIniciar.classList.remove('hidden');
            if (this.dom.btnPausar) this.dom.btnPausar.classList.add('hidden');
            if (this.dom.btnRetomar) this.dom.btnRetomar.classList.add('hidden');
        }
    }

    async enviarAcaoBackend(acao, segundosRestantes, detalhe = '') {
        try {
            const formData = new FormData();
            formData.append('prova_id', this.provaId);
            formData.append('acao', acao);
            formData.append('segundos_restantes', segundosRestantes);
            if (detalhe) formData.append('detalhe', detalhe);

            await fetch('atualizar_timer.php', {
                method: 'POST',
                body: formData
            });
        } catch (e) {
            console.error("Erro ao sincronizar timer com backend:", e);
        }
    }
}
