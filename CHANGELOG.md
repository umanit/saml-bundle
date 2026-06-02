# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.1] - 2026-06-06

### Added

- Exception spécifique en cas de provider introuvable ou désactivé

### Changed

- Un provider introuvable ou désactivé ne logue plus d’erreur
- La valeur par défaut des clefs de configuration de `binding` pour `sp.acs`, `sp.slo`, `idp.sso` et `idp.slo` est
  maintenant `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST`

### Fixed

- Protection de la clef de cache du `ResponseValidator`

## [2.0.0] - 2026-05-22

### Added

- Ajout du support de PHP 8.4
- [Dev] Mise en place de `umanit/dev-bundle`

### Removed

- Retrait du support de PHP <8.4
- Retrait du support de Symfony <7.4

## [1.0.32] - 2026-04-23

### Changed

- Mise en place de `#[IsGranted('PUBLIC_ACCESS')]` sur les différents controlleurs

## [1.0.31] - 2025-09-24

### Changed

- Retrait de la rotation des logs Monolog

## [1.0.30] - 2025-07-03

### Added

- Log : ajout de logs quand une erreur se produit au redirect

## [1.0.29] - 2025-07-03

### Added

- Configuration : option pour autoriser les certificats SSL auto-signés sur l'IDP

## [1.0.28] - 2025-03-27

### Fixed

- Command : gestion des erreurs de mise à jour des metadata

## [1.0.27] - 2025-03-14

### Fixed

- Correction du mode non strict pour Esalia

## [1.0.25] - 2025-03-05

### Fixed

- Changement du namespace pour l'EventDispatcher

## [1.0.24] - 2025-02-24

### Fixed

- [Redirect] Correction du format

## [1.0.23] - 2025-02-24

### Fixed

- [Redirect] Dynamisation du type de message

## [1.0.22] - 2025-02-24

### Added

- [RedirectAction] Ajout d'un event `BeforeSamlResponseEvent` pour un IDP initiated.

## [1.0.21] - 2025-02-24

### Fixed

- [Authentication] Meilleur gestion du RelayState si vide.

[Unreleased]: https://holygit.umanit.fr/umanit/saml-bundle/compare/2.0.1...HEAD

[2.0.1]: https://holygit.umanit.fr/umanit/saml-bundle/compare/2.0.0...2.0.1

[2.0.0]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.32...2.0.0

[1.0.32]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.31...1.0.32

[1.0.31]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.30...1.0.31

[1.0.30]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.29...1.0.30

[1.0.29]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.28...1.0.29

[1.0.28]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.27...1.0.28

[1.0.27]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.26...1.0.27

[1.0.26]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.25...1.0.26

[1.0.25]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.24...1.0.25

[1.0.24]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.23...1.0.24

[1.0.23]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.22...1.0.23

[1.0.22]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.21...1.0.22

[1.0.21]: https://holygit.umanit.fr/umanit/saml-bundle/compare/1.0.20...1.0.21
