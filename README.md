# hgabka/acl-bundle

A Symfony `AclBundle` saját fork-ja (`symfony/acl-bundle` helyettesítője: a `composer.json` `replace` mezője miatt a két csomag nem telepíthető egyszerre). A bundle a [`symfony/security-acl`](../security-acl) könyvtárat köti be Symfonyba, és objektum-szintű jogosultságkezelést (ACL) ad: ezzel adott domain objektumokhoz lehet hozzáférést rendelni felhasználókhoz vagy szerepekhez.

A fork célja a Symfony 7/8 és PHP 8.1+ kompatibilitás (`symfony/*` ^7.0|^8.1, `symfony/security-acl` ^7.1|^8.0). A névtér változatlanul `Symfony\Bundle\AclBundle`, így a `hgabka/utils-bundle` ACL funkciói (`AclHelper`, `AclVoter`, `hgabka:acl:*` parancsok) ugyanezt a névteret használják.

## Telepítés

```bash
composer require hgabka/acl-bundle
```

```php
// config/bundles.php
Symfony\Bundle\AclBundle\AclBundle::class => ['all' => true],
```

## Konfiguráció

```yaml
# config/packages/security.yaml
security:
    acl:
        connection: default        # a doctrine.dbal alatt beállított kapcsolat neve
```

Elérhető további beállítások: `cache` (`id` *vagy* `pool`, `prefix` – alapértelmezés `sf_acl_`), `provider`, `tables`, stb. (a `Configuration` osztály tartalmazza).

## Parancsok

| Parancs | Leírás |
|---|---|
| `acl:init` | az ACL táblák létrehozása az adatbázisban |
| `acl:set` | jogosultságok beállítása: `bin/console acl:set VIEW "App\Entity\Post:1" --user=jane --role=ROLE_ADMIN [--class-scope]` |

A Doctrine ORM-mel való használathoz az `AclSchemaListener` az ACL séma részeit a schema generálásba (`doctrine:schema:*`, migrációk) is beleteszi.

## Dokumentáció

Az ACL működéséről és használatáról: [src/Resources/doc/index.rst](src/Resources/doc/index.rst).

## Tesztek

```bash
composer install
./vendor/bin/simple-phpunit
```

## Licenc

MIT
