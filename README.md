# Meridiano Viagens — Back-office

Sistema interno de gestão para uma agência de turismo, desenvolvido em **PHP 8**, **MySQL/MariaDB**, HTML e CSS puros, sem framework. A aplicação centraliza clientes, destinos, pacotes, partidas, reservas, pagamentos e relatórios em uma única mesa de operações.

> Projeto acadêmico — Prova Oficial P2 de Desenvolvimento Web II.

---

## 1. Como executar

### Opção recomendada — Docker

Pré-requisitos: Docker e Docker Compose.

```bash
docker compose up -d --build
```

Acesse **http://localhost:8081**.

O banco é criado e populado automaticamente na primeira subida a partir de `database/schema.sql`.

Para usar outra porta:

```bash
APP_PORT=9090 docker compose up -d
```

Para recriar o banco do zero (apaga os dados existentes):

```bash
docker compose down -v
docker compose up -d
```

### Opção XAMPP

Pré-requisitos: Apache, PHP 8.1+ com `pdo_mysql` e `mbstring`, MySQL 8+ ou MariaDB 10.4+.

1. Copie a pasta do projeto para `htdocs`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. Importe `database/schema.sql` no phpMyAdmin. O script cria o banco `meridiano` e carrega os dados de demonstração.
4. Acesse `http://localhost/P2_DW/`.
5. Garanta permissão de escrita em `uploads/destinos` (Linux: `chmod -R 775 uploads`).

A configuração padrão usa `root` sem senha em `127.0.0.1`. Se o MySQL local tiver senha, ajuste `config.php` ou defina as variáveis `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` no ambiente do Apache.

### Credenciais de demonstração

| Campo | Valor |
| --- | --- |
| E-mail | `admin@meridiano.tur.br` |
| Senha | `meridiano123` |

---

## 2. Funcionalidades

- **Autenticação** com sessão, proteção CSRF, bloqueio de rotas internas e edição de perfil/senha.
- **Visão geral** com receita do mês, saldo em aberto, reservas ativas, ocupação, próximas partidas, vencimentos e agenda.
- **Clientes**: cadastro, edição, consulta, exclusão protegida, validação de CPF e bloqueio de e-mail/CPF duplicados.
- **Destinos**: CRUD com upload de imagem JPG/PNG/WebP (até 4 MB), remoção automática do arquivo enviado.
- **Pacotes**: CRUD vinculado a destino, com duração, preço, inclusos e situação ativa/inativa.
- **Partidas**: CRUD de viagens por pacote, com capacidade, ponto de encontro, observações e fluxo de situação.
- **Reservas**: criação com código sequencial (`MRD-ANO-0000`), controle de ocupação, edição e visão financeira.
- **Pagamentos**: lançamentos à vista ou parcelados, Pix/cartão/boleto/transferência, status pendente/pago/estornado e baixa de saldo.
- **Relatórios**: filtro por período, receita, reservas, ocupação, ranking de destinos, formas de pagamento e exportação CSV.
- **Interface responsiva** para estações de trabalho em 1280–1366 px, com tabelas compactas, tooltips em ações de ícone e componentes sem overflow.
- **Modo claro e escuro** alternável pela barra superior, com preferência salva no navegador e respeito à preferência do sistema no primeiro acesso.

---

## 3. Regras de negócio

### Ocupação e partidas

- A ocupação de uma partida soma os viajantes de reservas **pendentes e confirmadas**.
- Não é permitido vender mais lugares que a capacidade da partida.
- A capacidade não pode ser reduzida abaixo dos lugares já ocupados.
- O fluxo de situação da partida é: `planejada → confirmada → em_andamento → concluida`.
- Partidas podem ser canceladas, mas não quando possuem reservas ativas.
- Partidas concluídas ou canceladas não recebem novas reservas.

### Reservas e financeiro

- O valor total da reserva deve ser maior que zero e não pode ficar abaixo do valor já pago.
- O saldo disponível para novos lançamentos considera pagamentos **pagos e pendentes**, evitando comprometer o mesmo valor duas vezes.
- Quando a soma dos pagamentos recebidos alcança o valor total, a reserva é **confirmada automaticamente**.
- Reservas com pagamentos recebidos não podem ser canceladas nem excluídas.
- Ao cancelar uma reserva, parcelas pendentes são marcadas como estornadas.

### Pagamentos

- Pagamentos com status **pago** exigem data de pagamento; parcelas pendentes exigem vencimento.
- O sistema recusa lançamentos acima do saldo disponível da reserva.
- Pagamento recebido não pode ser excluído: deve ser editado para **estornado**, preservando o histórico.
- A exclusão de lançamentos não recebidos ressincroniza a situação financeira da reserva.

### Exclusões protegidas

- **Cliente** com reservas vinculadas não pode ser excluído.
- **Destino** com pacotes vinculados não pode ser excluído.
- **Pacote** com partidas vinculadas não pode ser excluído.
- **Partida** com reservas vinculadas não pode ser excluída.
- **Reserva** com pagamentos vinculados não pode ser excluída.

---

## 4. Estrutura do projeto

```text
├── app/                  # bootstrap, conexão PDO, helpers e funções de domínio
├── assets/               # CSS, JavaScript e imagens do sistema
├── controllers/          # regras de cada módulo (front controller)
├── database/schema.sql   # schema, views e dados de demonstração
├── docker/               # configuração do PHP no container
├── uploads/destinos/     # imagens enviadas pela aplicação
├── views/                # templates PHP server-side
├── config.php            # configuração de banco, uploads e fuso horário
├── docker-compose.yml
├── Dockerfile
└── index.php             # tabela de rotas
```

### Dados de demonstração

A carga inicial inclui **16 clientes**, **8 destinos**, **12 pacotes**, **20 partidas**, **43 reservas** e **64 pagamentos**, com datas relativas para manter o painel e os relatórios sempre movimentados.

---

## 5. Decisões técnicas

- **Front controller** em `index.php`, com rotas por `?r=modulo&acao=acao` e controllers isolados.
- **PDO com prepared statements** em todas as consultas.
- **Views SQL** (`vw_viagens_resumo` e `vw_reservas_financeiro`) concentram cálculos de ocupação, valores pagos e saldos.
- **CSRF token** em todos os formulários e ações de escrita.
- **Sanitização de saída** com escape HTML e validação server-side.
- **CSS autoral** com design system em variáveis, tipografia naval/timetable e layout adaptável a HD.
- **Tema claro/escuro** com tokens de cor, preferência persistida em `localStorage` e respeito ao tema do sistema.
- **JavaScript progressivo** para diálogos de confirmação, máscaras, cálculo automático de valores e tooltips.
