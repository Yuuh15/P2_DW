<div class="page-head">
    <div class="page-head__copy">
        <h1>Clientes</h1>
        <p class="page-head__sub"><?= (int) $total ?> cliente<?= $total === 1 ? '' : 's' ?> na base da agência. Abra a ficha para ver reservas, viagens e pagamentos.</p>
    </div>
    <div class="page-head__actions">
        <a class="button button--primary" href="<?= url('clientes', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Novo cliente</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= url('clientes') ?>" role="search">
    <input type="hidden" name="r" value="clientes">
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-clientes">Buscar clientes</label>
        <input type="search" id="busca-clientes" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por nome, e-mail, CPF ou cidade…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($busca !== ''): ?>
            <a class="toolbar__clear" href="<?= url('clientes') ?>">Limpar busca</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('users', 'icon', 13) ?> <?= (int) $total ?> cadastrados</span>
</form>

<section class="panel panel--flush">
    <?php if ($clientes): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Lista de clientes da agência</caption>
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Contato</th>
                        <th scope="col">Local</th>
                        <th scope="col">Reservas</th>
                        <th scope="col">Total contratado</th>
                        <th scope="col"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="avatar"><?= e(iniciais($cliente['nome'])) ?></span>
                                    <div class="person-cell__copy">
                                        <a class="cell-main" href="<?= url('clientes', ['acao' => 'ver', 'id' => $cliente['id']]) ?>"><?= e($cliente['nome']) ?></a>
                                        <div class="cell-sub"><?= e($cliente['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span><?= e(formatar_telefone($cliente['telefone'])) ?></span>
                                <div class="cell-sub"><?= e(formatar_cpf($cliente['cpf'])) ?></div>
                            </td>
                            <td>
                                <span><?= e($cliente['cidade'] ?: '—') ?></span>
                                <div class="cell-sub"><?= e($cliente['estado'] ?: '') ?></div>
                            </td>
                            <td>
                                <span class="cell-strong"><?= (int) $cliente['total_reservas'] ?></span>
                                <div class="cell-sub"><?= (int) $cliente['total_reservas'] === 1 ? 'reserva' : 'reservas' ?></div>
                            </td>
                            <td class="cell-strong mono"><?= e(money($cliente['total_contratado'])) ?></td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    <a class="action-link" href="<?= url('clientes', ['acao' => 'ver', 'id' => $cliente['id']]) ?>" aria-label="Ver ficha de <?= e($cliente['nome']) ?>" title="Ver ficha">
                                        <?= icon('eye', 'icon', 15) ?>
                                    </a>
                                    <a class="action-link" href="<?= url('clientes', ['acao' => 'editar', 'id' => $cliente['id']]) ?>" aria-label="Editar <?= e($cliente['nome']) ?>" title="Editar">
                                        <?= icon('edit', 'icon', 15) ?>
                                    </a>
                                    <form class="action-form" method="post"
                                          action="<?= url('clientes', ['acao' => 'excluir', 'id' => $cliente['id']]) ?>"
                                          data-confirm="Excluir o cliente <?= e($cliente['nome']) ?>? As reservas vinculadas precisam ser removidas antes."
                                          data-confirm-label="Excluir cliente">
                                        <?= csrf_field() ?>
                                        <button class="action-link action-link--danger" type="submit" aria-label="Excluir <?= e($cliente['nome']) ?>" title="Excluir">
                                            <?= icon('trash', 'icon', 15) ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include VIEWS_PATH . '/partials/pagination.php'; ?>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-state__mark"><?= icon('users', 'icon', 20) ?></span>
            <h3><?= $busca !== '' ? 'Nenhum cliente encontrado' : 'Nenhum cliente cadastrado ainda' ?></h3>
            <p><?= $busca !== '' ? 'Tente outro nome, e-mail, CPF ou cidade.' : 'Cadastre o primeiro cliente para começar a montar reservas e acompanhar o relacionamento.' ?></p>
            <?php if ($busca !== ''): ?>
                <a class="button button--secondary" href="<?= url('clientes') ?>">Limpar busca</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('clientes', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Cadastrar cliente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
