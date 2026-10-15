# Component Installer for Laminas and Mezzio Applications

[![Build Status](https://github.com/laminas/laminas-component-installer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas/laminas-component-installer/actions/workflows/continuous-integration.yml)
[![type-coverage](https://shepherd.dev/github/laminas/laminas-component-installer/coverage.svg)](https://shepherd.dev/github/laminas/laminas-component-installer)
[![Psalm level](https://shepherd.dev/github/laminas/laminas-component-installer/level.svg)](https://shepherd.dev/github/laminas/laminas-component-installer)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

This repository contains the Composer plugin class `Laminas\ComponentInstaller\ComponentInstaller`,
which provides Composer event hooks for the events:

- post-package-install
- post-package-uninstall

## Via Composer global install

To install the utility for use with all projects you use:

```bash
$ composer global require laminas/laminas-component-installer
```

## Per project installation

To install the utility for use with a specific project already managed by
composer:

```bash
$ composer require laminas/laminas-component-installer
```

## Writing packages that utilize the installer

Packages can opt-in to the workflow from laminas-component-installer by defining
one or more of the following keys under the `extra.laminas` configuration in their
`composer.json` file:

```json
"extra": {
  "laminas": {
    "component": "Component\\Namespace",
    "config-provider": "Classname\\For\\ConfigProvider",
    "module": "Module\\Namespace"
  }
}
```

- A **component** is for use specifically with laminas-mvc + laminas-modulemanager;
  a `Module` class **must** be present in the namespace associated with it.
  The setting indicates a low-level component that should be injected to the top
  of the modules list of one of:
    - `config/application.config.php`
    - `config/modules.config.php`
    - `config/development.config.php`

- A **module** is for use specifically with laminas-mvc + laminas-modulemanager;
  a `Module` class **must** be present in the namespace associated with it.
  The setting indicates a userland or third-party module that should be injected
  to the bottom of the modules list of one of:
    - `config/application.config.php`
    - `config/modules.config.php`
    - `config/development.config.php`

- A **config-provider** is for use with applications that utilize
  [laminas-config-aggregator](https://github.com/laminas/laminas-config-aggregator)
  (which may or may not be Mezzio applications). The class listed must be an
  invokable that returns an array of configuration, and will be injected at the
  top of:
    - `config/config.php`
    - any supported `container/config/*.php` file. When no remembered or
      automatic installation choice applies, the installer prompts you to
      choose which discovered configuration file to update. A file must contain
      a `Laminas\ConfigAggregator\ConfigAggregator` or
      `Mezzio\ConfigManager\ConfigManager` instance for the installer to offer
      it.

## Marking Packages to Auto-Install or to Be Ignored

At the project level, you can mark packages that expose configuration providers
and modules that you want to automatically inject via the `component-auto-installs`
key or in case you might want to permanently ignore a component, ignore components via `component-ignore-list`:

```json
{
  "extra": {
    "laminas": {
      "component-auto-installs": [
        "mezzio/mezzio",
        "mezzio/mezzio-helpers"
      ],
      "component-ignore-list": [
        "laminas/laminas-db"
      ]
    }
  }
}
```

This configuration must be made at the root package level (the package
_consuming_ configuration providing packages).
