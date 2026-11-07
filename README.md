# lliure-panel

A casca compartilhada dos apps do lliure: bootstrap, layout, menu e login.
O lliure-panel não tem app dentro dele. Os apps ficam na raiz de cada
projeto, cada um no seu repositório, e o lliure-panel dá a eles o painel
onde rodam.

## Por que a versão começa no 2

O lliure-panel é a **segunda geração do lliure**.

A primeira geração é o painel monolítico, que segue em
[`vinteenove/lliure`](https://bitbucket.org/vinteenove/lliure) (Bitbucket),
com versões até a `v11.x`. Nela, os apps vivem dentro do painel (`app/`,
`opt/`, `api/`), sob o namespace `LliurePanel\`. É ela que roda hoje o
`panel/` do Vimu, e continua lá até o último app ser migrado.

A segunda geração inverte essa relação: o painel vira uma casca e os apps
ficam do lado de fora. A mudança é grande o bastante para não caber como
"mais uma versão" da primeira. Por isso ganhou repositório próprio, e por
isso a versão começa em `v2`: não é um projeto novo do zero, é o lliure na
sua segunda forma.

### De onde veio o código

1. **Branch `v2` do `vinteenove/lliure`** (fevereiro/março de 2026): o
   esqueleto da segunda geração (Bootstrap, Auth, Menu, layout AdminLTE 4).
   A branch não tinha histórico em comum com a `master`, e todo esse
   histórico veio junto para cá.
2. **Três cópias que tinham divergido.** O espalu e o musicasdeigreja
   copiaram essa branch para a pasta `lliure/` e foram recebendo ajustes por
   conta própria. O Vimu fez um spike de coexistência em cima dela. Em
   novembro de 2026 as três foram reunidas aqui:
   - **espalu e musicasdeigreja:** sessão de 30 dias e configuração de
     banco lida de `etc/database.php`;
   - **musicasdeigreja:** cache do Twig;
   - **spike do Vimu:** modo embarcado (ver abaixo).

A primeira tag, `v2.0.0-alpha.1`, marca esse ponto de encontro.

## O que muda de uma geração para a outra

| | 1ª geração (`vinteenove/lliure`) | 2ª geração (`lliure-panel`) |
|---|---|---|
| Onde ficam os apps | Dentro do painel (`app/`) | Na raiz do projeto, cada um com seu repo |
| Papel do lliure | O sistema inteiro | A casca: layout, menu, login |
| Namespace | `LliurePanel\` | `Lliure\` |
| Layout | AdminLTE 3 / Bootstrap 4 | AdminLTE 4 / Bootstrap 5 |
| Pacote | `lliure/lliure` | `vinteenove/lliure-panel` |

## Estrutura de um projeto

```
projeto/
├── composer.json      ← requer vinteenove/lliure-panel e junta os apps (merge-plugin)
├── etc/database.php   ← credenciais (fora do git)
├── vendor/vinteenove/lliure-panel/   ← este pacote, instalado pelo composer
├── cache/twig/        ← cache do Twig (apagar a cada deploy)
├── cifras/            ← um app, com repositório próprio
└── site/              ← outro app
```

O lliure-panel entra pelo composer, como qualquer pacote. O repositório é
privado, então o projeto declara o VCS:

```json
"repositories": [
  { "type": "vcs", "url": "git@github.com:vinteenove/lliure-panel.git", "no-api": true }
],
"require": {
  "vinteenove/lliure-panel": "^2.0@alpha"
}
```

O `no-api` faz o composer clonar por SSH. Sem ele, ele tenta o zip pela API
do GitHub, que responde 404 para repositório privado sem token. O `@alpha` é
necessário enquanto não houver `v2.0.0` e o projeto usar
`minimum-stability: stable`.

Os apps não são pacotes publicados: cada um é um checkout na raiz, e o
`wikimedia/composer-merge-plugin` junta o `composer.json` deles no do projeto
(`"merge-plugin": {"include": ["cifras/composer.json"]}`).

Até a `v2.0.0-alpha.5`, o lliure-panel era um clone em `lliure/` na raiz do
projeto, carregado por um autoload escrito à mão. Essas tags não instalam pelo
composer: elas exigem o AdminLTE como pacote, e ele não é `stable`. Use a
`v2.0.0-alpha.6` ou mais nova.

## Criar um app

O gabarito é o [pipeline](https://github.com/vinteenove/pipeline), o
primeiro app desta geração em produção. O segundo, o
[Fiscon](https://github.com/vinteenove/Fiscon), seguiu a mesma forma.

### Pastas

```
cifras/
├── composer.json    ← só o autoload do app (nada de require do lliure-panel)
├── src/             ← AppServiceProvider, Controllers, Models
├── views/           ← templates Twig, acessados como @cifras/...
├── migrations/      ← 001_xxx.sql, 002_xxx.sql... idempotentes
└── assets/          ← logo, favicon
```

```json
{
    "name": "vinteenove/cifras",
    "type": "library",
    "require": { "php": "^8.1" },
    "autoload": { "psr-4": { "Cifras\\": "src/" } }
}
```

As dependências comuns (lliure-core, router, twig, dibi) já vêm do
lliure-panel. O app só declara o que ele usa e o projeto ainda não tem.

### O provider

O app implementa as duas interfaces. A de rotas é opcional no modo sozinho,
mas o modo embarcado só conhece o app por ela:

```php
use Lliure\Bootstrap;
use Lliure\Menu\MenuBuilder;
use Lliure\Menu\MenuItem;
use Lliure\RoutesProviderInterface;
use Lliure\ServiceProviderInterface;
use Router\Router;

final class AppServiceProvider implements ServiceProviderInterface, RoutesProviderInterface
{
    public static function boot(Bootstrap $bootstrap): void
    {
        // views em @cifras/...
        $bootstrap->registerApp('cifras', dirname(__DIR__));
    }

    public static function registerMenu(MenuBuilder $menu): void
    {
        $menu->addItem(new MenuItem(
            label: 'Cifras',
            url:   '/cifras',
            icon:  'fas fa-music',
            order: 10,
            // opcional: esconde o item de quem não pode acessar
            visible: fn(): bool => true,
        ));
        // Vários itens sob um título: $menu->addGroup('Título', [$item, ...], 'fas fa-x', 0)
    }

    public static function routes(Router $router): void
    {
        $router->get('/',          [Controllers\Cifras::class, 'index']);
        $router->post('/cifras',   [Controllers\Cifras::class, 'criar']);
        $router->get('/cifras/{id}', [Controllers\Cifras::class, 'ver']);
    }
}
```

As rotas usam o `\Router\Router` (pacote `lliure/router`). O controller
recebe `(ServerRequestInterface $request, array $vars)` e devolve uma string
(HTML) ou uma `ResponseInterface`.

### Views

Toda tela estende o layout do painel, e o resto é bloco:

```twig
{% extends "layout.twig" %}

{% block title %}Cifras{% endblock %}
{% block content_header %}Cifras{% endblock %}
{% block content %}
    ...
{% endblock %}
```

| Bloco | Para que serve |
|---|---|
| `content` | o corpo da tela |
| `content_header` | o título acima do corpo |
| `title` | o `<title>` da aba |
| `styles` | CSS próprio da tela, dentro do `<head>` |
| `navbar_left`, `navbar_right` | itens extras na barra do topo |
| `body_class` | classes do `<body>` (tela cheia, sem menu...) |
| `sidebar`, `footer`, `user_panel` | trocar ou esconder essas partes; `{% block footer %}{% endblock %}` tira o rodapé |

Para renderizar: `Bootstrap::getInstance()->getTwig()->render('@cifras/index.twig', $dados)`.

### Banco

- **Cada linha pertence a uma instância** (coluna `instance`), e toda
  consulta filtra por ela. Peça a instância a um lugar só no app (no pipeline,
  `Pipeline\Instancia::atual()`), nunca a uma constante espalhada. O nome é
  `instance`, não `account`: o núcleo do Vimu chamou de `account` até
  nov/2026 e a troca custou três releases.
- **Migração separada do deploy.** O usuário da aplicação não roda DDL. A
  migração roda antes de publicar, e o código precisa funcionar com ela já
  aplicada e também sem ela (coluna nova sempre opcional na primeira versão).
- **`SET NAMES utf8mb4` no `boot()`**, se as tabelas forem utf8mb4. A conexão
  que o Vimu abre é utf8, e emoji vira `?`.

### No Vimu

Além do repositório do app, entram no repositório `vinteenove/vimu`:

1. `src/Hosts/Cifras.php`, com o `AppConfig` (`provider`, `serviceId`, marca,
   login). É ele que faz o login, a escolha de instância e o SSO serem os mesmos
   dos outros apps.
2. `cifras/composer.json` no `merge-plugin` da raiz, e `/cifras` no
   `.gitignore`.
3. Um `case` no `deploy/deploy-app` e o workflow de release no repositório do
   app.

O passo a passo, com as armadilhas que já custaram tempo, está em
`docs/deploy-dos-apps-v2.md` no repositório do Vimu.

## Dois modos de uso

**Sozinho.** O lliure-panel conecta o banco com `config/database.php` (que
lê `etc/database.php` na raiz do projeto), cuida da sessão (30 dias) e faz o
login com a tabela `lliure_users` (`migrations/001_users.sql`). É o caso do
espalu e do musicasdeigreja.

A migração não cria nenhum usuário. O primeiro vai ser criado por uma tela de
boas-vindas (welcome), com a senha escolhida por quem instala. A tela ainda
não existe; até lá, o primeiro usuário entra direto no banco. Até a
`v2.0.0-alpha.1`, a
migração criava um `admin` com senha fixa. Em instalações dessa época, confira
se esse usuário existe e troque a senha dele.

**Embarcado.** O lliure-panel sobe dentro de outro sistema que já tem
usuário, banco e sessão. É o caso do Vimu, onde login, instância e
permissões continuam sendo do Vimu. Nesse modo:

- se o autoloader do Composer já foi carregado, o Bootstrap não procura um
  `vendor/` próprio;
- se já existe uma conexão `dibi` aberta, ela é reaproveitada. Conectar de
  novo trocaria o banco por baixo de quem conectou antes;
- quem hospeda informa o usuário logado com
  `Lliure\Auth\Auth::setIdentityResolver(fn() => ...)`, sem segundo login e
  sem precisar de linha em `lliure_users`;
- quem hospeda chama `Bootstrap::setProjectRoot($raiz)` **antes** do primeiro
  `getInstance()`. É de lá que saem o `cache/twig` e o `vendor/autoload.php`.
  Sem a chamada, vale a pasta acima do pacote, que dentro do `vendor/` é
  `vendor/vinteenove`. O cache ficaria fora do lugar que o deploy limpa, e o
  layout novo não apareceria.

Nos dois modos, uma falha de conexão com o banco estoura na hora, em vez de
seguir em silêncio até a primeira consulta.

## Versões

- `v2.0.0-alpha.N` enquanto a forma de integrar os apps ainda estiver
  mudando. Cada alpha pode quebrar compatibilidade com a anterior.
- `v2.0.0` quando o primeiro app construído inteiramente sobre esta geração
  (o pipeline do Vimu) estiver em produção.

Cada projeto escolhe a versão pelo composer, e atualizar vira uma decisão de
cada projeto:

```
composer update vinteenove/lliure-panel
composer update vinteenove/lliure-panel --with vinteenove/lliure-panel:2.0.0-alpha.6   # uma tag exata
```

Depois de atualizar, apague o `cache/twig`: em produção o Twig não relê
template alterado.

No Vimu, publicar uma release deste repositório já sobe a versão. O workflow
roda `deploy-app lliure-panel <tag>` no servidor, que faz o `composer update
--with` daquela tag e limpa o cache. Para voltar uma versão, use o Run
workflow com a tag antiga, a partir da `v2.0.0-alpha.6`.

Uma tag nova deste pacote precisa funcionar com os apps que já estão em
produção: eles sobem em dias diferentes. Para mudar um nome, mantenha o antigo
como apelido `@deprecated` por uma release.
