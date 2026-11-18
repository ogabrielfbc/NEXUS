# 🚀 NEXUS TECH

**NEXUS TECH** é um projeto de loja virtual desenvolvido com PHP e MySQL, com uma interface moderna voltada para o universo da tecnologia e dos setups gamers.

O projeto tem como objetivo simular o funcionamento de um e-commerce, permitindo o gerenciamento de produtos, clientes e pedidos, além de um futuro sistema de compras utilizando uma moeda virtual chamada **NEXUS Coins**.

## 🖥️ Tecnologias utilizadas

- **PHP** — programação e funcionalidades do sistema.
- **HTML5** — estrutura das páginas.
- **CSS3** — estilização e identidade visual.
- **Bootstrap 5** — componentes e responsividade.
- **JavaScript** — interatividade das páginas.
- **MySQL** — armazenamento dos dados.
- **PDO** — conexão e comunicação com o banco de dados.
- **Apache / XAMPP** — ambiente de desenvolvimento.

## ⚙️ Funcionalidades desenvolvidas

### Área administrativa

- Login exclusivo para administradores.
- Proteção das páginas administrativas.
- Painel de administração.
- Cadastro de produtos.
- Listagem e pesquisa de produtos.
- Edição de produtos.
- Exclusão de produtos.
- Gerenciamento de imagens dos produtos.
- Suporte para modelos 3D no formato GLB.
- Página de gerenciamento de pedidos.
- Página de gerenciamento de clientes.

### Loja virtual

- Página inicial.
- Exibição de produtos em destaque.
- Listagem de produtos.
- Organização por categorias.
- Interface com tema escuro e identidade visual própria.

## 🪙 NEXUS Coins

Uma das funcionalidades planejadas é um sistema de moeda virtual chamado **NEXUS Coins**.

A proposta é permitir que os clientes tenham um saldo de moedas e utilizem esse saldo para realizar compras simuladas dentro da loja.

## 📂 Estrutura do projeto

```text
nexus-tech/
│
├── admin/
│   ├── login.php
│   ├── verificar.php
│   ├── proteger.php
│   ├── painel.php
│   ├── produtos.php
│   ├── adicionar.php
│   ├── editar.php
│   ├── excluir.php
│   ├── pedidos.php
│   └── clientes.php
│
├── config/
│   └── database.php
│
├── uploads/
│   ├── produtos/
│   └── modelos/
│
├── index.php
├── produtos.php
├── produto2.php
├── style.css
└── .env
```

## 💻 Como executar o projeto

1. Instale o [XAMPP](https://www.apachefriends.org/).
2. Inicie os serviços Apache e MySQL.
3. Coloque a pasta `nexus-tech` dentro do diretório `htdocs`.
4. Crie o banco de dados `nexus_tech` no phpMyAdmin.
5. Configure a conexão com o banco de dados no arquivo de configuração e no `.env`, conforme necessário.
6. Acesse o projeto pelo navegador:

```text
http://localhost/nexus-tech/
```

> **Importante:** o arquivo `.env` contém configurações privadas e não deve ser enviado ao GitHub.

## 📌 Próximas etapas

- [ ] Implementar cadastro de clientes.
- [ ] Implementar login de clientes.
- [ ] Criar área pessoal do cliente.
- [ ] Desenvolver carteira NEXUS Coins.
- [ ] Implementar carrinho de compras.
- [ ] Desenvolver finalização de compras simuladas.
- [ ] Integrar compras ao gerenciamento de pedidos.
- [ ] Criar histórico de compras.
- [ ] Realizar testes e melhorias finais.

## 🎯 Objetivo do projeto

Desenvolver uma loja virtual funcional, aplicando conhecimentos de desenvolvimento web, programação em PHP, banco de dados relacionais e construção de interfaces.

---

**NEXUS TECH — Tecnologia, performance e inovação.**
