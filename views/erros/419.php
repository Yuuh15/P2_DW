<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('dashboard') ?>"><?= icon('arrow-left', 'icon', 13) ?> Voltar à visão geral</a>
        <h1>Sessão expirada</h1>
        <p class="page-head__sub">O formulário enviado perdeu a validação de segurança. Recarregue a página e tente novamente.</p>
    </div>
</div>

<section class="panel">
    <div class="empty-state" style="padding: 64px 24px;">
        <span class="empty-state__mark" style="font-family: var(--font-mono); font-size: 24px; width: 52px; height: 52px;">419</span>
        <h3>Proteção contra envios repetidos</h3>
        <p>Por segurança, a plataforma não aceita um formulário sem token válido. Volte à tela anterior, atualize a página e reenvie os dados.</p>
        <div class="button-row" style="justify-content: center;">
            <a class="button button--primary" href="<?= url('dashboard') ?>"><?= icon('grid', 'icon', 15) ?> Voltar à visão geral</a>
            <a class="button button--secondary" href="<?= e($_SERVER['HTTP_REFERER'] ?? url('dashboard')) ?>"><?= icon('arrow-left', 'icon', 15) ?> Retornar à tela anterior</a>
        </div>
    </div>
</section>
