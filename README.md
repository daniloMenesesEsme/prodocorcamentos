# ProDoc Orçamentos

Aplicativo SaaS para geração e envio de orçamentos profissionais via WhatsApp e PDF.

## Objetivo

O **ProDoc Orçamentos** foi criado para facilitar a vida de profissionais autônomos e pequenas empresas que precisam enviar orçamentos de forma rápida e profissional. Com poucos cliques, o usuário preenche os dados do serviço, gera um orçamento formatado e envia diretamente pelo WhatsApp do cliente — ou exporta em PDF.

### Funcionalidades

- Cadastro de conta com 7 dias de acesso gratuito
- Login com verificação de licença e data de expiração
- Salvamento dos dados da empresa/prestador na conta do usuário
- Geração de orçamento formatado para envio via WhatsApp
- Geração de orçamento em PDF (versão desktop)
- Funciona como aplicativo Android nativo

## Tecnologias Utilizadas

| Camada | Tecnologia |
|--------|-----------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla ES6+) |
| Backend | PHP (sem framework) |
| Banco de Dados | MySQL |
| Mobile | Capacitor v8 (Android wrapper) |
| PDF | html2pdf.js |
| Ambiente local | Laragon |

## Estrutura do Projeto

```
prodocorcamentos/
├── www/                    # Aplicação web
│   ├── index.html          # Interface principal (Login, Cadastro, Gerador)
│   ├── config.php          # Conexão com o banco de dados
│   ├── login.php           # Endpoint de autenticação
│   ├── api_cadastrar.php   # Endpoint de cadastro de usuários
│   └── salvar_config.php   # Endpoint para salvar dados da empresa
├── android/                # Projeto Android (Capacitor)
├── capacitor.config.json   # Configuração do Capacitor
└── package.json            # Dependências Node/Capacitor
```

## Configuração do Banco de Dados

Crie o banco `db_prodoc` no MySQL e execute o seguinte SQL:

```sql
CREATE DATABASE IF NOT EXISTS db_prodoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db_prodoc;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20),
    data_expiracao DATE NOT NULL,
    status_assinatura VARCHAR(50) DEFAULT 'trial',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_empresa VARCHAR(255),
    whatsapp_empresa VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

## Como Rodar Localmente

1. Instale o [Laragon](https://laragon.org/) e inicie os serviços Apache e MySQL
2. Clone este repositório dentro de `C:\laragon\www\`
3. Configure o banco de dados conforme o script SQL acima
4. Acesse `http://localhost/prodocorcamentos/www/`

## Build Android

```bash
# Instalar dependências
npm install

# Copiar arquivos web para o projeto Android
npx cap sync android

# Abrir no Android Studio
npx cap open android
```

## Desenvolvedor

**Danilo Meneses**
GitHub: [daniloMenesesEsme](https://github.com/daniloMenesesEsme)
