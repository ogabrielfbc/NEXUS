# 🚀 NEXUS TECH

**NEXUS TECH** é uma loja virtual de tecnologia desenvolvida com PHP e MySQL, com uma interface moderna inspirada no universo dos setups gamers e produtos tecnológicos.

O projeto simula o funcionamento de um e-commerce, oferecendo gerenciamento administrativo, cadastro de clientes, carrinho de compras, pedidos, avaliações de produtos e um sistema de moeda virtual chamado **NEXUS Coins**.

## 🖥️ Tecnologias utilizadas

- **PHP** — programação e funcionalidades do sistema.
- **HTML5** — estrutura das páginas.
- **CSS3** — estilização e identidade visual.
- **Bootstrap 5** — componentes e responsividade.
- **JavaScript** — interatividade das páginas.
- **MySQL** — armazenamento dos dados.
- **PDO** — conexão e comunicação com o banco de dados.
- **Apache / XAMPP** — ambiente de desenvolvimento.
- **Three.js** — suporte à visualização de modelos 3D.

## ⚙️ Funcionalidades desenvolvidas

### 🔐 Área administrativa

- Login exclusivo para administradores.
- Proteção de acesso às páginas administrativas.
- Painel administrativo com informações do sistema.
- Cadastro, edição, listagem e exclusão de produtos.
- Gerenciamento de imagens dos produtos.
- Suporte para modelos 3D no formato GLB.
- Gerenciamento de pedidos.
- Gerenciamento de clientes.
- Cadastro e edição de cupons de desconto.
- Ativação e desativação de cupons.
- Histórico de utilização dos cupons.
- Moderação de avaliações dos produtos.

### 🛒 Loja virtual

- Página inicial com identidade visual própria.
- Exibição de produtos em destaque.
- Listagem e organização por categorias.
- Página individual de produtos.
- Galeria de imagens.
- Visualização de modelos 3D.
- Carrinho de compras.
- Aplicação de cupons de desconto.
- Finalização de compras simuladas.

### 👤 Área do cliente

- Cadastro de clientes.
- Login e autenticação.
- Área pessoal do cliente.
- Edição de dados cadastrais.
- Alteração de senha com verificação da senha atual.
- Visualização de pedidos.
- Histórico de compras.
- Carteira de NEXUS Coins.
- Histórico de movimentações da carteira.
- Envio de avaliações para produtos comprados.

### 🪙 Sistema NEXUS Coins

O NEXUS TECH possui um sistema próprio de moeda virtual chamado **NEXUS Coins (NC)**.

- Cada novo cliente recebe **10.000 NEXUS Coins** ao criar uma conta.
- Carteira individual para cada cliente.
- Registro de créditos e débitos.
- Histórico de movimentações.
- Utilização das moedas em compras simuladas.
- Registro das movimentações realizadas.

**Conversão utilizada:** 1 NC equivale a R$ 1,00 dentro da simulação do projeto.

### 🎟️ Sistema de cupons

- Cadastro de cupons de desconto.
- Descontos por porcentagem ou valor fixo.
- Controle de validade.
- Limite de utilizações.
- Ativação e desativação de cupons.
- Registro de utilização dos descontos.

### ⭐ Sistema de avaliações

- Clientes podem avaliar produtos comprados.
- Notas de 1 a 5 estrelas.
- Comentários sobre os produtos.
- Controle de avaliações pela administração.
- Aprovação, ocultação e exclusão de avaliações.

## 📂 Estrutura do projeto

```text
nexus-tech/
│
├── admin/
│   ├── login.php
│   ├── painel.php
│   ├── produtos.php
│   ├── adicionar.php
│   ├── editar.php
│   ├── excluir.php
│   ├── pedidos.php
│   ├── clientes.php
│   ├── cupons.php
│   ├── avaliacoes.php
│   └── ...
│
├── cliente/
│   ├── cadastro.php
│   ├── login.php
│   ├── minha_conta.php
│   ├── carteira.php
│   ├── pedidos.php
│   ├── avaliar_produto.php
│   ├── editar_dados.php
│   └── ...
│
├── config/
│   └── database.php
│
├── uploads/
│   ├── produtos/
│   └── modelos/
│
├── index.php
├── carrinho.php
├── adicionar_carrinho.php
├── finalizar_compra.php
├── produto.php
├── produto2.php
├── style.css
└── .env
```

## 💻 Como executar o projeto

1. Instale o [XAMPP](https://www.apachefriends.org/).
2. Inicie os serviços Apache e MySQL.
3. Coloque a pasta `nexus-tech` dentro do diretório `htdocs`.
4. Crie o banco de dados `nexus_tech` no phpMyAdmin.
5. Configure as credenciais de conexão no arquivo `.env`, conforme a configuração do projeto.
6. Acesse pelo navegador:

```text
http://localhost/nexus-tech/
```

**Observação:** o arquivo `.env` contém configurações privadas e não deve ser enviado ao GitHub.

## 💳 Sobre os pagamentos

O projeto trabalha com compras simuladas e moeda virtual. Não possui integração com gateways de pagamento ou processamento de transações financeiras reais.

## 🎯 Objetivo do projeto

O NEXUS TECH foi desenvolvido para colocar em prática conhecimentos de desenvolvimento web, PHP, banco de dados relacionais, integração entre sistemas, gerenciamento de sessões e construção de interfaces.

O projeto também representa uma experiência prática de desenvolvimento de um sistema completo, desde a área administrativa até a experiência de compra do cliente.

---

**NEXUS TECH — Tecnologia, performance e inovação.**