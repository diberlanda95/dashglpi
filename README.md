# 📊 Dashboard GLPI Pro

Dashboard web avançado para visualização e análise de dados do **GLPI**, desenvolvido inteiramente em **PHP**, com frontend em **HTML, CSS e JavaScript**. O projeto consome dados diretamente do banco do GLPI e/ou via API interna, exibindo métricas em tempo real para suporte à gestão de TI.

---

## 🚀 Visão Geral

O **Dashboard GLPI Pro** foi criado para oferecer uma visão clara, moderna e centralizada do ambiente GLPI, facilitando o acompanhamento de chamados, SLAs, desempenho da equipe técnica e ativos de TI.

O sistema funciona como uma camada de visualização sobre o GLPI, sem alterar sua estrutura original.

---

## ✨ Principais Funcionalidades

* 📈 Visão geral de chamados (abertos, em andamento, solucionados e fechados)
* ⏱️ Monitoramento de SLA e chamados críticos
* 👨‍💻 Ranking e desempenho de técnicos (gamificação)
* 📊 Gráficos dinâmicos (linha, barras e comparativos mensais)
* 🖥️ Inventário de ativos com visualização detalhada
* 🔔 Sistema de notificações visuais
* 📺 Modo TV (rotação automática de telas)
* 🌗 Tema claro e escuro
* 🔄 Atualização automática dos dados

---

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP (puro)
* **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
* **Gráficos:** Chart.js
* **Banco de Dados:** MySQL / MariaDB (GLPI)
* **Integração:** Banco de dados e endpoints internos do GLPI

---

## 📂 Estrutura do Projeto

```bash
Dashboard-GLPI-Pro/
├── index.php          # Interface principal do dashboard
├── api.php            # Endpoints internos (JSON)
├── db.php             # Conexão com banco de dados GLPI
├── script.js          # Lógica do frontend e consumo da API
├── style.css          # Estilos e layout do dashboard
├── index-teste.php    # Ambiente de testes / validações
├── assets/            # Ícones, imagens e recursos estáticos
├── docs/              # Documentação e imagens do projeto
└── README.md
```

---

## 🔁 Fluxo de Funcionamento

1. **index.php** renderiza a interface do dashboard
2. **script.js** faz requisições AJAX para `api.php`
3. **api.php** consulta o banco do GLPI via `db.php`
4. Os dados são retornados em **JSON**
5. O frontend atualiza cards, tabelas e gráficos dinamicamente

---

## ⚙️ Instalação e Configuração

### 1️⃣ Pré-requisitos

* GLPI instalado e funcional
* PHP 7.4 ou superior
* MySQL / MariaDB
* Servidor web (Apache ou Nginx)

### 2️⃣ Clonar o projeto

```bash
git clone https://github.com/seu-usuario/dashboard-glpi-pro.git
```

### 3️⃣ Configurar conexão com o banco

Edite o arquivo `db.php` com as credenciais do banco do GLPI:

```php
$host = 'localhost';
$db   = 'glpi';
$user = 'usuario';
$pass = 'senha';
```

### 4️⃣ Publicar no servidor

Copie o projeto para o diretório público do servidor web:

```bash
/var/www/html/dashboard-glpi-pro
```

Acesse no navegador:

```
http://localhost/dashboard-glpi-pro
```

---

## 🔐 Segurança

* Utilize usuário de banco com permissões **somente leitura**
* Restrinja acesso ao dashboard via firewall ou autenticação
* Não exponha o dashboard diretamente à internet sem proteção

---

## 📊 Exemplos de Telas

Adicione imagens na pasta `docs/`:

```md
![Dashboard Principal](docs/dashboard.png)
![Ranking de Técnicos](docs/ranking.png)
```

---

## 🧪 Roadmap

* [ ] Autenticação de usuários
* [ ] Filtros avançados por data e técnico
* [ ] Exportação de relatórios (PDF / Excel)
* [ ] Cache de dados para melhor performance
* [ ] Suporte a múltiplas instâncias GLPI

---

## 🤝 Contribuição

Contribuições são bem-vindas!

1. Faça um fork do projeto
2. Crie uma branch (`feature/minha-feature`)
3. Commit suas alterações
4. Faça push para a branch
5. Abra um Pull Request

---

## 📄 Licença

Este projeto está licenciado sob a licença **MIT**.

---

## 👤 Autor

**Diogo Berlanda**
🐙 GitHub: [https://github.com/seu-usuario](https://github.com/diberlanda95)
🔗 LinkedIn: [https://linkedin.com/in/seu-perfil](https://www.linkedin.com/in/diogo-berlanda-8436b4132)

---

⭐ Se este projeto te ajudou, deixe uma estrela no repositório!
