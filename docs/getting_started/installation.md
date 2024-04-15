# Installation

## Prérequis

- PHP 8.1 ou supérieur
- Symfony 6.4 ou supérieur

## Via composer
```bash
composer require umanit/saml-bundle
```

### Configuration des routes

Par exemple dans le dossier route rajouter un fichier `umanit_saml.yaml` avec le contenu suivant

```yaml
umanit_saml:
    resource: '@UmanitSamlBundle/config/route.yaml'
    prefix: /saml2
```

## Configuration
Créer un fichier `umanit_saml.yaml` dans le dossier de configuration `config/packages`.

```yaml
umanit_saml:
    # Définir le dossier où seront contenus les certificats
    certificat_path: '%kernel.project_dir%/certs'

    # Déclaration des différentes configurations (IDP, SP)
    providers:
```

### Configuration d'un provider de type IDP
Prenons par exemple le cas d'un interfaçage avec OneLogin.<br>
Pour notre cas l'identifiant de configuration du provider sera `onelogin`.

Nous allons tout d'abord générer un certificat via la commande Symfony.<br>
Voir la section [Génération d'un certificat et d'une clé privée](#génération-dun-certificat-et-dune-clé-privée)

```bash
symfony console umanit:saml:create-certificat onelogin
```

Ensuite, nous allons rajouter la configuration :

```yaml
# ...
    providers:
        # App (SP) -> demande d'identité vers Onelogin (IDP) -> renvoi de l'identité App (SP) 
        onelogin:
            sp:
                # Doit respecter le format (Unique identifiant de l'application)
                entity_id: https://saml-bundle.wip/saml2/onelogin
                # Défini le format de l'identifiant.
                name_id_format: !php/const LightSaml\SamlConstants::NAME_ID_FORMAT_EMAIL
                # Défini le certificat X509 généré précédement.
                x509cert: '%kernel.project_dir%/certs/onelogin/saml.crt'
                # Défini la clé privée générée précédement.
                private_key:
                    path: '%kernel.project_dir%/certs/onelogin/saml.key'
            idp:
                # Définition du chemin ou récupérer les métadonnées
                # Ce chemin peut être une URL, un chemin vers un fichier ou directement en chaîne de caractères.
                metadata: 'https://app.onelogin.com/saml/metadata/b25dbecc-e3be-4b7d-b151-e066faa415d8'
```


### Configuration de l'authentification

Dans le fichier security.yaml, il faut définir le provider à utiliser pour l'authentification.
Par défaut le bundle fourni plusieurs user provider qui permettent de gérer l'authentification via SAML.

Les user provider fournis par défaut sont :

- `saml` : Permet de fournie un utilisateur sans lien avec la base de données.
- `saml_entity` : Permet de fournir un utilisateur en lien avec la base de données.
- `saml_scoped` : Permet de définir un user provider par configuration SAML. (Par exemple onelogin utilise le user provider saml_entity)

Il est possible de définir un provider de type `saml_scoped` qui permet de définir un provider de type `saml` avec des restrictions de rôles.

```yaml 
security:
    providers:
        # saml_user_provider_without_database:
        #     saml:
        #         user_class: App\Entity\User
        #         default_roles: ['ROLE_USER']
                
        saml_user_provider_linked_with_database:
            saml_entity:
                class: App\Entity\User
                property: email
                default_roles: ['ROLE_USER']
                # Optionnel, permet de définir des rôles en fonction des groupes de l'utilisateur
                # Ici, on définit que l'utilisateur appartenant au groupe UMANIT\grp_suivitemps_admin
                # aura le rôle ROLE_ADMIN, et que l'utilisateur appartenant au groupe UMANIT\grp_suivitemps_chef_de_projets
                # aura le rôle ROLE_CHEF_DE_PROJET.
                roles_mapping:
                    - { attribute_name: 'usergroups', type: 'memberof', needed: 'UMANIT\grp_suivitemps_admin', role: 'ROLE_ADMIN' }
                    - { attribute_name: 'usergroups', type: 'memberof', needed: 'UMANIT\grp_suivitemps_chef_de_projets', role: 'ROLE_CHEF_DE_PROJET' }

        saml:
            saml_scoped:
                providers:
                    onelogin: saml_user_provider_linked_with_database

    firewalls:
        main:
            # Configuration de l'authentification par SAML
            saml:
                provider: saml
                login_path: app_login
                # Optionnel : Permet de définir des restrictions de connexion en fonction des attributs SAML.
                # Ici, on définit que l'utilisateur doit appartenir au groupe UMANIT\grp_suivitemps_admin
                # pour pouvoir se connecter.
                saml_restrictions:
                    - { attribute_name: 'usergroups', type: 'memberof', needed: 'UMANIT\grp_suivitemps_admin' }
```

### Configuration de la class User

La class User doit implémenter l'interface `SamlUserInterface` et doit contenir les méthodes suivantes :

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Umanit\SamlBundle\Security\User\SamlUserInterface;

class User implements SamlUserInterface
{   
    public function __construct(
        private array $roles = [],
        private array $samlAttributes = []
    ) {
    }

    public function getRoles(): array 
    {
        return $this->roles;   
    }
   
    public function setRoles(array $roles = []): self 
    {
        $this->roles = $roles;   
        
        return $this;
    }
    
    public function setSamlIdentifier(string $identifier): self
    {
        $this->email = $identifier;
        
        return $this;
    }

    public function getSamlAttributes(): array 
    {
        return $this->samlAttributes;
    }            

    public function setSamlAttributes(array $samlAttributes): void 
    {
        $this->samlAttributes = $samlAttributes;
    }
}
```

## Génération d'un certificat et d'une clé privée
Afin de pouvoir signer et vérifier l'authenticité des échanges
entre plusieurs applications, il nous faut un certificat et une clé
privée.

### Génération via une commande Linux
```bash
openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout saml.key -out saml.crt 
```

Placer ensuite les 2 fichiers dans le dossier défini en configuration
`umanit_saml.certificat_path` et dans un sous dossier qui portera la clé du provider en configuration.

### Génération via une commande Symfony

```bash
symfony console umanit:saml:create-certificat {provider_name}

Description:
  Create a new X509 certificate and private key

Usage:
  umanit:saml:create-certificat [options] [--] [<name>]

Arguments:
  name                       Name of the certificate

Options:
      --days[=DAYS]          Number of days the certificate is valid [default: 365]
      --keyname[=KEYNAME]    Name of the private key file [default: "saml.key"]
      --certname[=CERTNAME]  Name of the certificate file [default: "saml.crt"] 
```

Par défaut les fichiers seront générés dans le dossier défini en configuration
`umanit_saml.certificat_path`/`{provider_name}`
