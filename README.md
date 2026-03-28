# ProDoc Orçamentos

> Plataforma SaaS para geração de orçamentos profissionais, gestão de clientes, controle financeiro e catálogo de produtos — com app Android nativo via Capacitor.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Android](https://img.shields.io/badge/Android-3DDC84?style=for-the-badge&logo=android&logoColor=white)
![Capacitor](https://img.shields.io/badge/Capacitor-119EFF?style=for-the-badge&logo=capacitor&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)

---

## Sobre o Projeto

O **ProDoc Orçamentos** nasceu para resolver um problema real de autônomos e pequenas empresas: a falta de uma ferramenta simples, rápida e profissional para criar e enviar orçamentos.

O sistema evoluiu de um simples gerador de orçamentos para uma **plataforma SaaS completa**, com:
- Autenticação por licença com período de trial
- App Android nativo (mesma base de código da web)
- Gestão de empresa, clientes, produtos e serviços
- Controle financeiro com receitas x despesas
- Dashboard com indicadores em tempo real

---

## Funcionalidades Implementadas

### Autenticação e Segurança
- [x] Cadastro com 7 dias de trial gratuito
- [x] Login com verificação de licença e validade
- [x] Proteção contra força bruta (5 tentativas → bloqueio de 15 min)
- [x] Validação completa de campos no backend (PHP)
- [x] Sessão persistida via `localStorage`

### Gerador de Orçamentos
- [x] Orçamento com múltiplos itens (adicionar/remover dinamicamente)
- [x] Adicionar itens direto do catálogo de produtos
- [x] Cálculo automático do total
- [x] Validade da proposta configurável (dias)
- [x] Envio formatado via WhatsApp (deep link)
- [x] Exportação em PDF (html2pdf.js)
- [x] Histórico persistido no banco de dados

### Cadastro da Empresa
- [x] Nome, CNPJ/CPF, WhatsApp, e-mail, site
- [x] Endereço completo com busca automática por CEP (ViaCEP API)
- [x] Auto-preenchimento de dados via CNPJ (BrasilAPI)

### Catálogo de Produtos e Serviços
- [x] Cadastro com nome, preço, unidade e categoria (produto/serviço)
- [x] Edição e exclusão com verificação de propriedade
- [x] Seleção direta no gerador de orçamentos via overlay

### Dashboard
- [x] Cards: orçamentos e receita do mês
- [x] Saldo do mês (receitas - despesas)
- [x] Totais acumulados gerais
- [x] Top 5 clientes por valor
- [x] Gráfico de barras dos últimos 6 meses

### Gestão de Clientes
- [x] Cadastro com nome, WhatsApp, e-mail, CPF/CNPJ, cidade e observações
- [x] Edição e exclusão

### Gestão Financeira
- [x] Resumo do mês: receitas (orçamentos) x despesas x saldo
- [x] Lançamento de despesas com categoria e data
- [x] Listagem cronológica de despesas

### App Android (Capacitor)
- [x] Build nativo Android sem React Native / Flutter
- [x] Comunicação HTTP com servidor local via WiFi
- [x] Suporte a cleartext HTTP (Capacitor v8 + AndroidManifest)
- [x] Mesmo código-base da versão web

---

## Stack Tecnológica

| Camada | Tecnologia | Detalhe |
|--------|-----------|---------|
| Frontend | HTML5 + CSS3 + JS Vanilla ES6+ | SPA sem framework |
| Backend | PHP (sem framework) | Endpoints REST via PDO |
| Banco de Dados | MySQL 8 | Laragon local |
| Mobile | Capacitor v8 | Android wrapper nativo |
| PDF | html2pdf.js | Geração client-side |
| CEP | ViaCEP API | Gratuita, sem auth |
| CNPJ | BrasilAPI | Gratuita, sem auth |
| Versionamento | Git + GitHub | Deploy manual |

---

## Arquitetura

```
prodocorcamentos/
├── www/                          # Aplicação web (servida pelo Laragon)
│   ├── index.html                # SPA principal — todas as telas e lógica JS
│   ├── config.php                # Conexão PDO com o banco de dados
│   │
│   ├── # Autenticação
│   ├── login.php                 # POST: autenticar usuário
│   ├── api_cadastrar.php         # POST: criar conta
│   │
│   ├── # Configurações do usuário
│   ├── salvar_config.php         # POST: salvar nome/zap da tela principal
│   ├── buscar_config.php         # POST: buscar nome/zap
│   │
│   ├── # Empresa
│   ├── salvar_empresa.php        # POST: UPSERT dados da empresa
│   ├── buscar_empresa.php        # POST: buscar dados da empresa
│   │
│   ├── # Orçamentos
│   ├── salvar_orcamento.php      # POST: salvar orçamento gerado
│   ├── buscar_orcamentos.php     # POST: listar histórico
│   │
│   ├── # Catálogo de Produtos
│   ├── salvar_produto.php        # POST: criar/editar produto
│   ├── listar_produtos.php       # POST: listar produtos do usuário
│   ├── excluir_produto.php       # POST: excluir produto
│   │
│   ├── # Dashboard
│   ├── buscar_dashboard.php      # POST: indicadores e estatísticas
│   │
│   ├── # Clientes
│   ├── salvar_cliente.php        # POST: criar/editar cliente
│   ├── listar_clientes.php       # POST: listar clientes
│   ├── excluir_cliente.php       # POST: excluir cliente
│   │
│   └── # Financeiro
│       ├── salvar_despesa.php    # POST: lançar despesa
│       ├── listar_financeiro.php # POST: resumo + lista de despesas
│       └── excluir_despesa.php   # POST: excluir despesa
│
├── android/                      # Projeto Android gerado pelo Capacitor
├── capacitor.config.json         # Configuração do Capacitor
├── package.json
├── README.md
└── ROADMAP.md                    # Histórico completo do projeto
```

---

## Banco de Dados

```sql
CREATE DATABASE IF NOT EXISTS db_prodoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_prodoc;

-- Usuários do sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20),
    data_expiracao DATE NOT NULL,
    status_assinatura VARCHAR(50) DEFAULT 'trial',
    tentativas_login INT DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configurações rápidas (nome/zap da tela principal)
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_empresa VARCHAR(255),
    whatsapp_empresa VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Dados completos da empresa
CREATE TABLE empresa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    nome_fantasia VARCHAR(150),
    cnpj_cpf VARCHAR(20),
    whatsapp VARCHAR(20),
    email VARCHAR(100),
    cep VARCHAR(10),
    endereco VARCHAR(200),
    numero VARCHAR(20),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    site VARCHAR(200),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Histórico de orçamentos gerados
CREATE TABLE orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cliente VARCHAR(150) NOT NULL,
    itens JSON NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    tipo ENUM('whatsapp','pdf') DEFAULT 'whatsapp',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Catálogo de produtos e serviços
CREATE TABLE produtos_servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unidade VARCHAR(20) DEFAULT 'un',
    categoria ENUM('produto','servico') DEFAULT 'servico',
    descricao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Clientes cadastrados
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20),
    email VARCHAR(100),
    cpf_cnpj VARCHAR(20),
    cidade VARCHAR(100),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Despesas financeiras
CREATE TABLE despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    categoria ENUM('materiais','servicos','transporte','alimentacao','outros') DEFAULT 'outros',
    data_despesa DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

---

## Como Rodar Localmente

### Pré-requisitos
- [Laragon](https://laragon.org/) (Apache + MySQL)
- [Node.js](https://nodejs.org/) 18+
- [Android Studio](https://developer.android.com/studio) (para build Android)

### Web

```bash
# 1. Clone o repositório dentro da pasta www do Laragon
cd C:\laragon\www
git clone https://github.com/daniloMenesesEsme/prodocorcamentos.git

# 2. Crie o banco de dados (MySQL Workbench ou HeidiSQL)
# Execute o SQL completo da seção acima

# 3. Acesse no navegador
# http://localhost/prodocorcamentos/www/
```

### Android

```bash
# Instalar dependências do Capacitor
npm install

# Sincronizar arquivos web com o projeto Android
npx cap sync android

# Abrir no Android Studio e gerar o APK
npx cap open android
```

> **Importante:** Para rodar no Android físico, edite a constante `SERVER_URL` em `www/index.html` com o IP da sua máquina na rede local.

---

## Roadmap

| Fase | Status | Descrição |
|------|--------|-----------|
| 1 | ✅ Concluída | Correções base, segurança e Android |
| 2 | ✅ Concluída | Cadastro da empresa + menu de navegação |
| 3 | ✅ Concluída | Catálogo de produtos e serviços |
| 4 | ✅ Concluída | Dashboard com indicadores |
| 5 | ✅ Concluída | Gestão de clientes |
| 6 | ✅ Concluída | Gestão financeira |
| 7 | 🔄 Em desenvolvimento | Relatórios por período, cliente e serviço |
| 8 | 📋 Planejada | Controle de estoque |
| 9 | 📋 Planejada | Emissão de documentos fiscais (NFCe/NFS-e) |

---

## Desenvolvedor

**Danilo Meneses**
GitHub: [@daniloMenesesEsme](https://github.com/daniloMenesesEsme)

---

*Projeto em desenvolvimento ativo — novas funcionalidades sendo adicionadas semanalmente.*
