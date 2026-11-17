<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('dashboard') ?>"><?= icon('arrow-left', 'icon', 13) ?> Voltar à visão geral</a>
        <h1>Página não encontrada</h1>
        <p class="page-head__sub">O endereço acessado não existe ou o registro foi removido da mesa de operações.</p>
    </div>
</div>

<section class="panel">
    <div class="empty-state" style="padding: 64px 24px;">
        <span class="empty-state__mark" style="font-family: var(--font-mono); font-size: 24px; width: 52px; height: 52px;">404</span>
        <h3>Rota fora do mapa</h3>
        <p>Confira o endereço ou use a navegação lateral para voltar a uma área válida da plataforma.</p>
        <div class="button-row" style="justify-content: center;">
            <a class="button button--primary" href="<?= url('dashboard') ?>"><?= icon('grid', 'icon', 15) ?> Ir para a visão geral</a>
            <a class="button button--secondary" href="<?= url('reservas') ?>"><?= icon('ticket', 'icon', 15) ?> Abrir reservas</a>
        </div>
    </div>
</section>
