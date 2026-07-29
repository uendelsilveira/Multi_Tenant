# 🚀 Projeto Base MultiTenant MultiDatabase + Filament PHP

[![PHP](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%2F13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-v3.2-FDAE4B?style=for-the-badge&logo=laravel&logoColor=white)](https://filamentphp.com)
[![Stancl Tenancy](https://img.shields.io/badge/Stancl_Tenancy-v3.8-4F46E5?style=for-the-badge)](https://tenancyforlaravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%207-4479A1?style=for-the-badge)](https://phpstan.org)
[![Docker Sail](https://img.shields.io/badge/Docker-Sail-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://laravel.com/docs/sail)

Estrutura base completa, moderna e pronta para produção para desenvolvimento rápido de aplicações SaaS **Multi-Tenant com Bancos de Dados Isolados (Multi-Database)** utilizando **Laravel**, **Filament PHP v3**, **Stancl Tenancy v3**, e **Laravel Sail**.

---

## ⚡ Início Rápido (Quick Start)

Siga os passos abaixo para colocar a aplicação rodando em poucos minutos no seu ambiente local.

### 1. Clonar o Repositório e Configurar Variáveis de Ambiente

```bash
cp .env.example .env
```

### 2. Iniciar o Ambiente Docker com Laravel Sail

```bash
./vendor/bin/sail up -d
```

### 3. Instalar Dependências PHP e Node.js

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

### 4. Gerar Chave da Aplicação

```bash
./vendor/bin/sail artisan key:generate
```

### 5. Executar Migrações Centrais e Seeders

```bash
./vendor/bin/sail artisan migrate --seed
```

### 6. Configurar Hosts Locais (Subdomínios para Tenancy)

Edite o arquivo `/etc/hosts` no seu sistema operacional (ou `C:\Windows\System32\drivers\etc\hosts` no Windows) e adicione o domínio central e os subdomínios de teste apontando para `127.0.0.1`:

```text
127.0.0.1   localhost
127.0.0.1   empresa1.localhost
127.0.0.1   empresa2.localhost
```

### 7. Iniciar o Assets Bundler (Vite)

```bash
./vendor/bin/sail npm run dev
```

Pronto! A aplicação já estará disponível em:
- **Painel Central (Admin)**: [http://localhost/admin](http://localhost/admin)
- **Painel do Tenant**: [http://empresa1.localhost/admin](http://empresa1.localhost/admin)

---

## ✨ Melhorias e Funcionalidades Implementadas

### 🏢 1. Arquitetura Multi-Tenant Multi-Database (`stancl/tenancy` v3.8)
- **Isolamento Total por Banco de Dados**: Cada inquilino (tenant) possui seu próprio banco de dados MySQL criado e gerenciado automaticamente na criação da conta.
- **Resolução de Tenancy por Subdomínio**: Identificação dinâmica do tenant através do middleware `InitializeTenancyBySubdomain`.
- **Proteção de Domínios Centrais**: Utilização de `PreventAccessFromCentralDomains` para garantir que rotas de tenant não vazem para o painel central.
- **Separação Estruturada de Migrações e Rotas**:
  - Migrações centrais em `database/migrations/`
  - Migrações isoladas do tenant em `database/migrations/tenant/`
  - Rotas de tenants isoladas em `routes/tenant.php`

### 🎨 2. Painéis Duplos no Filament PHP v3.2
- **Painel Central (`AdminPanelProvider`)**:
  - Acessível em `localhost/admin`.
  - Destinado à administração global da plataforma, cadastro de tenants, domínios e assinaturas.
  - Esquema de cores: **Primary Blue**.
- **Painel do Tenant (`TenantPanelProvider`)**:
  - Acessível em `{subdominio}.localhost/admin`.
  - Destinado à operação interna de cada empresa/tenant.
  - Integrado nativamente aos middlewares de tenancy do Stancl.
  - Esquema de cores: **Primary Amber**.

### 👥 3. Gestão de Usuários e Perfis de Acesso (RBAC NATIVO)
- Enum nativo PHP `UserRole` (`App\Enums\UserRole`):
  - `SuperAdmin` (`super_admin`)
  - `Admin` (`admin`)
  - `Manager` (`manager`)
  - `Operator` (`operator`)
- Separação entre Usuários Centrais (`App\Models\User`) e Usuários de Tenants (`App\Models\TenantUser`).
- Seeders automatizados e padronizados para preenchimento de dados de teste.

### 🛡️ 4. Qualidade de Código & Análise Estática Nível 7 (PHPStan / Larastan)
- Análise estática de código configurada no **Nível 7 (Level 7)** via `phpstan.neon`.
- Integração perfeita com `larastan/larastan` para prevenção contínua de erros de tipagem e runtime bugs em tempo de desenvolvimento.

### 🐳 5. Ambiente Containerizado com Laravel Sail
- Suporte a Docker out-of-the-box via Laravel Sail.
- Serviços integrados: **PHP 8.4 / 8.3**, **MySQL 8.0** e **Redis alpine**.
- Execução padronizada e segura usando `./vendor/bin/sail`.

---

## 🔑 Credenciais Padrão (Seeders)

Todos os usuários abaixo são gerados automaticamente com a senha padrão: **`password`**

### 🏢 Painel Central (`http://localhost/admin`)
| Nome | E-mail | Role |
| :--- | :--- | :--- |
| **Super Admin** | `admin@usdeveloper.com.br` | `SuperAdmin` |
| **Central Admin** | `admin@central.com` | `Admin` |
| **Central Manager** | `manager@central.com` | `Manager` |
| **Central Operator** | `operator@central.com` | `Operator` |

### 🏬 Painel do Tenant (`http://{tenant}.localhost/admin`)
| Nome | E-mail | Role |
| :--- | :--- | :--- |
| **Super Admin** | `admin@usdeveloper.com.br` | `SuperAdmin` |
| **Tenant Admin** | `admin@tenant.com` | `Admin` |
| **Tenant Manager** | `manager@tenant.com` | `Manager` |
| **Tenant Operator** | `operator@tenant.com` | `Operator` |

---

## 🛠️ Comandos Úteis de Desenvolvimento

Todos os comandos devem ser executados via **Sail**:

### Gerenciamento do Ambiente Docker
```bash
# Subir os containers em background
./vendor/bin/sail up -d

# Parar os containers
./vendor/bin/sail down
```

### Banco de Dados & Tenancy
```bash
# Executar migrações do banco central
./vendor/bin/sail artisan migrate

# Executar migrações em TODOS os bancos de dados de tenants
./vendor/bin/sail artisan tenancy:migrate

# Executar seeders em TODOS os bancos de dados de tenants
./vendor/bin/sail artisan tenancy:db:seed
```

### Qualidade de Código & Testes
```bash
# Executar análise estática do PHPStan (Level 7)
./vendor/bin/sail composer phpstan

# Executar a suíte de testes automatizados
./vendor/bin/sail artisan test

# Formatação de código com Laravel Pint
./vendor/bin/sail composer exec pint
```

---

## 📂 Estrutura de Arquivos Relevantes

```text
.
├── app/
│   ├── Enums/
│   │   └── UserRole.php              # Enum de perfis de usuário (SuperAdmin, Admin, Manager, Operator)
│   ├── Models/
│   │   ├── Tenant.php                # Model principal do Tenant
│   │   ├── Domain.php                # Model de subdomínios do Tenant
│   │   ├── User.php                  # Model de usuários centrais
│   │   └── TenantUser.php            # Model de usuários do tenant
│   └── Providers/
│       └── Filament/
│           ├── AdminPanelProvider.php  # Configuração do Painel Central
│           └── TenantPanelProvider.php # Configuração do Painel do Tenant
├── config/
│   └── tenancy.php                   # Configuração principal da tenancy multi-database
├── database/
│   ├── migrations/                   # Migrações do banco central
│   │   └── tenant/                   # Migrações executadas em cada banco de tenant
│   └── seeders/
│       ├── CentralUserSeeder.php     # Seeder de usuários centrais
│       ├── TenantUserSeeder.php      # Seeder de usuários do tenant
│       └── DatabaseSeeder.php
├── routes/
│   ├── web.php                       # Rotas centrais
│   └── tenant.php                    # Rotas dos tenants (subdomínio)
├── compose.yml                       # Definição do Docker Sail (PHP, MySQL, Redis)
└── phpstan.neon                      # Configuração do PHPStan no Nível 7
```

---

## 👨‍💻 Autor

Desenvolvido por **Uendel Silveira**  
*Developer Web*

---

## 📜 Licença

Este projeto é um software de código aberto licenciado sob a [MIT License](https://opensource.org/licenses/MIT).
