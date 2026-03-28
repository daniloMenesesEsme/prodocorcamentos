# ROADMAP — ProDoc Orçamentos

> Documento de referência completo do projeto. Registra tudo que foi construído, decisões técnicas tomadas, problemas resolvidos e o que ainda será desenvolvido. Use este documento para retomar o contexto em qualquer ferramenta de IA ou para apresentar o histórico do projeto.

---

## Visão Geral do Sistema

**ProDoc Orçamentos** é uma plataforma SaaS progressiva para autônomos e pequenas empresas.

- **Frontend:** HTML5 + CSS3 + JavaScript Vanilla ES6+ (SPA sem framework)
- **Backend:** PHP puro com PDO (sem Laravel, sem framework)
- **Banco:** MySQL 8 via Laragon
- **Mobile:** Capacitor v8 — o mesmo HTML/JS roda como app Android nativo
- **Servidor local:** Laragon (Apache + MySQL)
- **IP local da máquina de dev:** `192.168.0.5`
- **URL local browser:** `http://localhost/prodocorcamentos/www/`
- **Banco de dados:** `db_prodoc`
- **Usuário MySQL:** `root` / senha vazia (padrão Laragon)
- **Repositório:** https://github.com/daniloMenesesEsme/prodocorcamentos

---

## Padrões Técnicos Importantes

### Detecção de ambiente (browser vs Android)
```javascript
// No Capacitor, o path é /index.html
// No browser, o path é /prodocorcamentos/www/index.html
const isNativeApp = !window.location.pathname.includes('/prodocorcamentos/');
const SERVER_URL = isNativeApp
    ? 'http://192.168.0.5/prodocorcamentos/www'   // Android → IP fixo
    : '/prodocorcamentos/www';                     // Browser → caminho absoluto
```

### Padrão de endpoint PHP
Todos os arquivos PHP seguem este padrão:
```php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php'; // abre $conn (PDO)

try {
    // lógica aqui usando $conn->prepare(...)
    echo json_encode(['status' => 'sucesso', ...]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
```

### Navegação SPA
A função `alternarTelas(qual)` esconde todas as telas e exibe apenas a solicitada. O menu inferior (`#bottomNav`) aparece apenas nas telas autenticadas.

### Após qualquer mudança no www/
```bash
npx cap sync android   # obrigatório antes de build Android
```

---

## Estrutura de Arquivos (completa)

```
prodocorcamentos/
├── www/
│   ├── index.html                # SPA — todas as telas, CSS e JS
│   ├── config.php                # Conexão PDO → $conn
│   ├── login.php                 # Autenticação com bloqueio por brute force
│   ├── api_cadastrar.php         # Cadastro com validação completa
│   ├── salvar_config.php         # Salvar nome/zap (tela principal)
│   ├── buscar_config.php         # Buscar nome/zap
│   ├── salvar_empresa.php        # UPSERT empresa completa
│   ├── buscar_empresa.php        # Buscar empresa por usuario_id
│   ├── salvar_orcamento.php      # Salvar orçamento gerado
│   ├── buscar_orcamentos.php     # Listar histórico (limit 50)
│   ├── salvar_produto.php        # Criar/editar produto (verifica ownership)
│   ├── listar_produtos.php       # Listar produtos ordenados por categoria
│   ├── excluir_produto.php       # Excluir produto (verifica ownership)
│   ├── buscar_dashboard.php      # Indicadores e estatísticas
│   ├── salvar_cliente.php        # Criar/editar cliente
│   ├── listar_clientes.php       # Listar clientes ordenados por nome
│   ├── excluir_cliente.php       # Excluir cliente
│   ├── salvar_despesa.php        # Lançar despesa financeira
│   ├── listar_financeiro.php     # Resumo financeiro + lista de despesas
│   └── excluir_despesa.php       # Excluir despesa
├── android/                      # Projeto Android (Capacitor)
├── capacitor.config.json         # androidScheme: "http" (crucial para HTTP no Android)
├── package.json
├── README.md
└── ROADMAP.md                    # Este arquivo
```

---

## Banco de Dados Completo

```sql
-- ============================================
-- SCHEMA COMPLETO — db_prodoc
-- ============================================

CREATE DATABASE IF NOT EXISTS db_prodoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_prodoc;

-- Fase 1: Usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,           -- bcrypt via password_hash()
    whatsapp VARCHAR(20),
    data_expiracao DATE NOT NULL,
    status_assinatura VARCHAR(50) DEFAULT 'trial',
    tentativas_login INT DEFAULT 0,        -- Fase 1: brute force
    bloqueado_ate DATETIME NULL,           -- Fase 1: bloqueio temporário
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fase 1: Configurações rápidas (nome/zap da tela principal)
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_empresa VARCHAR(255),
    whatsapp_empresa VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Fase 2: Empresa
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

-- Fase 1 (expandida na Fase 6): Orçamentos
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

-- Fase 3: Catálogo de produtos e serviços
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

-- Fase 5: Clientes
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

-- Fase 6: Despesas financeiras
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

## Histórico de Fases

---

### FASE 1 — Correções base, segurança e Android
**Status:** ✅ Concluída

**O que foi feito:**
- Corrigido bug `usuario_id = 'undefined'` que quebrava INSERTs no banco (o campo `resultado.id` não existia na resposta do servidor)
- Corrigido erro "Erro ao conectar com o servidor" no Android — URLs relativas não funcionam em app Capacitor
- Implementado `SERVER_URL` inteligente: IP fixo no Android, caminho absoluto no browser
- Configurado `androidScheme: "http"` no `capacitor.config.json` (Capacitor v8 usa HTTPS por padrão, bloqueando fetch HTTP)
- Adicionado `android:usesCleartextTraffic="true"` no `AndroidManifest.xml`
- Exibição do nome do usuário e validade da licença na tela principal
- Feedback visual nos botões durante carregamento (disabled + texto "Aguarde...")
- Auto-preenchimento de nome e zap ao entrar no sistema (busca do banco se não tiver em localStorage)
- Multi-itens no orçamento com soma automática
- Validade da proposta configurável em dias
- Histórico de orçamentos salvo no MySQL (antes era só localStorage)
- Validação completa de campos em `api_cadastrar.php` (nome ≥ 3 chars, e-mail válido, senha ≥ 6, WhatsApp 10-11 dígitos)
- Senha agora é hasheada APÓS a validação (bug: antes era hasheada antes, tornando o erro de "senha curta" inútil)
- Proteção contra força bruta no login: 5 tentativas → bloqueio de 15 minutos

**Arquivos criados/alterados:**
- `www/index.html` (refatoração completa)
- `www/login.php` (brute force protection)
- `www/api_cadastrar.php` (validação + hash correto)
- `www/salvar_config.php`, `www/buscar_config.php`
- `www/salvar_orcamento.php`, `www/buscar_orcamentos.php`
- `capacitor.config.json`
- `android/app/src/main/AndroidManifest.xml`

---

### FASE 2 — Cadastro da empresa + menu de navegação
**Status:** ✅ Concluída

**O que foi feito:**
- Nova tela `#telaEmpresa` com formulário completo de dados da empresa
- Busca automática de endereço por CEP usando a API ViaCEP (gratuita)
- Auto-preenchimento de dados pelo CNPJ usando a BrasilAPI (gratuita)
- Menu de navegação inferior (`#bottomNav`) com botões: Início, Empresa, Catálogo, Histórico
- Tabela `empresa` criada no banco de dados

**APIs externas utilizadas:**
- `https://viacep.com.br/ws/{CEP}/json/` — busca endereço por CEP
- `https://brasilapi.com.br/api/cnpj/v1/{CNPJ}` — busca dados do CNPJ

**Arquivos criados:**
- `www/salvar_empresa.php`
- `www/buscar_empresa.php`

---

### FASE 3 — Catálogo de produtos e serviços
**Status:** ✅ Concluída

**O que foi feito:**
- Nova tela `#telaProdutos` com formulário de cadastro
- Campos: nome, preço, unidade (un/h/m²/m/kg/dia/serv), categoria (produto/serviço), descrição
- Listagem agrupada por categoria com botões editar/excluir
- Overlay `#overlayCatalogo` para selecionar produto direto no gerador de orçamentos
- Botão "Adicionar do Catálogo" integrado à tela principal
- Tabela `produtos_servicos` criada no banco

**Arquivos criados:**
- `www/salvar_produto.php`
- `www/listar_produtos.php`
- `www/excluir_produto.php`

---

### FASE 4 — Dashboard com indicadores
**Status:** ✅ Concluída

**O que foi feito:**
- Nova tela `#telaDashboard` acessível pelo menu inferior
- Cards: orçamentos do mês, receita do mês, despesas do mês, saldo
- Cards: total geral de orçamentos e receita acumulada
- Top 5 clientes por valor (com medalhas)
- Gráfico de barras simples dos últimos 6 meses

**Arquivos criados:**
- `www/buscar_dashboard.php`

---

### FASE 5 — Gestão de clientes
**Status:** ✅ Concluída

**O que foi feito:**
- Nova tela `#telaClientes` com formulário de cadastro
- Campos: nome, WhatsApp, e-mail, CPF/CNPJ, cidade, observações
- CRUD completo com verificação de propriedade (usuario_id)
- Tabela `clientes` criada no banco

**Arquivos criados:**
- `www/salvar_cliente.php`
- `www/listar_clientes.php`
- `www/excluir_cliente.php`

---

### FASE 6 — Gestão financeira
**Status:** ✅ Concluída

**O que foi feito:**
- Nova tela `#telaFinanceiro`
- Resumo do mês: Receitas (soma de orçamentos) x Despesas x Saldo
- Formulário para lançar despesas (descrição, valor, categoria, data)
- Categorias: Materiais, Serviços terceiros, Transporte, Alimentação, Outros
- Listagem cronológica com exclusão
- Tabela `despesas` criada no banco

**Navegação reorganizada:**
- Novo menu com 5 botões: 🏠 Início | 📊 Dashboard | 👥 Clientes | 💰 Financeiro | ☰ Mais
- Botão "Mais" abre overlay com: Empresa, Catálogo, Histórico, Sair

**Arquivos criados:**
- `www/salvar_despesa.php`
- `www/listar_financeiro.php`
- `www/excluir_despesa.php`

---

### Bugs corrigidos após implementação

| Bug | Causa | Solução |
|-----|-------|---------|
| `Erro ao conectar com o servidor` no browser | `SERVER_URL = ''` gerava `/login.php` que resolvia para raiz do domínio | Alterado para `SERVER_URL = '/prodocorcamentos/www'` |
| `Unknown database 'prodocorcamentos'` | Novos PHPs usavam nome errado do banco | Todos alterados para `require_once 'config.php'` (banco: `db_prodoc`) |

---

## Próximas Fases

---

### FASE 7 — Relatórios
**Status:** 🔄 Em desenvolvimento

**O que será feito:**
- Tela de relatórios com filtros por período (mês/ano)
- Relatório de orçamentos por cliente
- Relatório por tipo de serviço/produto
- Totais por período
- Exportação em PDF dos relatórios

**Arquivos a criar:**
- `www/buscar_relatorios.php`
- Nova tela `#telaRelatorios` em `index.html`

---

### FASE 8 — Controle de estoque
**Status:** 📋 Planejada

**O que será feito:**
- Vinculação de quantidade em estoque aos produtos cadastrados
- Registro de entradas e saídas
- Alertas de estoque mínimo
- Baixa automática ao usar produto em orçamento

**Tabelas a criar:**
```sql
ALTER TABLE produtos_servicos ADD COLUMN estoque_atual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE produtos_servicos ADD COLUMN estoque_minimo DECIMAL(10,2) DEFAULT 0;

CREATE TABLE movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    observacao VARCHAR(200),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### FASE 9 — Documentos Fiscais (NFCe / NFS-e)
**Status:** 📋 Planejada (alta complexidade)

**O que será feito:**
- Emissão de NFCe (Nota Fiscal de Consumidor Eletrônica) via SEFAZ
- Emissão de NFS-e (Nota Fiscal de Serviço Eletrônica) via prefeitura
- Upload e gestão de certificado digital A1
- Ambiente de homologação antes de produção

**Requisitos externos:**
- Certificado digital A1 (arquivo .pfx) ou A3 (token físico)
- Credenciais SEFAZ por estado
- Biblioteca PHP para geração de XML (ex: NFePHP)
- Homologação prévia no ambiente de testes da SEFAZ

> ⚠️ Esta fase exige integração com APIs governamentais e varia por estado/município. É a fase de maior complexidade do projeto.

---

## Commits Relevantes

| Hash | Descrição |
|------|-----------|
| `19bd9da` | fix: corrigir banco de dados nos novos endpoints (db_prodoc via config.php) |
| `3e2b0a0` | fix: corrigir SERVER_URL no browser (caminho relativo incompleto causava 404) |
| `2ba3e18` | feat: fases 4, 5 e 6 - dashboard, clientes e financeiro |
| `159285b` | feat: fase 3 - catálogo de produtos e serviços |
| `a2b2950` | feat: busca automática de CEP e CNPJ na tela da empresa |
| `4378129` | feat: fase 2 - cadastro da empresa e menu de navegação inferior |
| `5c6184d` | feat: limite de tentativas de login com bloqueio temporário |
| `8d487d0` | feat: validação de campos nos endpoints PHP |
| `1276894` | feat: histórico de orçamentos persistido no banco de dados |

---

*Última atualização: março de 2026*
