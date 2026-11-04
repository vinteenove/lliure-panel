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
├── composer.json      ← junta lliure/ e os apps num vendor só (merge-plugin)
├── etc/database.php   ← credenciais (fora do git)
├── lliure/            ← clone deste repositório (ignorado pelo git do projeto)
├── cifras/            ← um app, com repositório próprio
└── site/              ← outro app
```

O `composer.json` da raiz usa o `wikimedia/composer-merge-plugin` para
incluir `lliure/composer.json` e o `composer.json` de cada app.

## Contrato de um app

Cada app expõe um provider:

```php
use Lliure\Bootstrap;
use Lliure\Menu\MenuBuilder;
use Lliure\Menu\MenuItem;
use Lliure\ServiceProviderInterface;

class AppServiceProvider implements ServiceProviderInterface
{
    public static function boot(Bootstrap $bootstrap): void
    {
        // registra as views do app como namespace Twig (@cifras/...)
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
    }
}
```

As rotas usam o `\Router\Router` (pacote `lliure/router`).

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
  sem precisar de linha em `lliure_users`.

Nos dois modos, uma falha de conexão com o banco estoura na hora, em vez de
seguir em silêncio até a primeira consulta.

## Versões

- `v2.0.0-alpha.N` enquanto a forma de integrar os apps ainda estiver
  mudando. Cada alpha pode quebrar compatibilidade com a anterior.
- `v2.0.0` quando o primeiro app construído inteiramente sobre esta geração
  (o pipeline do Vimu) estiver em produção.

Cada projeto fixa a tag que usa (`git checkout v2.0.0-alpha.1` dentro de
`lliure/`), em vez de copiar a pasta. Assim fica claro quem está em qual
versão, e atualizar vira uma decisão de cada projeto.
