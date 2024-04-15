# Installation 

## Composer package
```bash
composer require umanit/saml-bundle
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

## Configuration d'un provider de type IDP
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
        # App (SP) -> demande d'indentité vers Onelogin (IDP) -> renvoi de l'identité App (SP) 
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


## Configuration de l'authentification

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
                # Optionnel, permet de définir une méthode de récupération des utilisateurs
                # Ici ne pourront se connecter que les utilisateurs ayant un groupe 'UMANIT\grp_users_umanit'
                # dans l'attribut 'usergroups'
                restrictions:
                    - { attribute_name: 'usergroups', type: 'memberof', needed: 'UMANIT\grp_users_umanit' }
                # Optionnel, permet de définir des rôles en fonction des groupes de l'utilisateur
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
```

## Configuration de la class User

La class User doit implémenter l'interface `SamlUserInterface` et doit contenir les méthodes suivantes :

```php
<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;use Umanit\SamlBundle\Security\User\SamlUserInterface;

class User implements UserInterface, SamlUserInterface
{
    public function getRoles(): array 
    {
    
    }
    
    public function eraseCredentials() 
    {
    
    }
  
    public function getUserIdentifier(): string
    {
    
    }
    
    public function setRoles(array $roles = []): array 
    {
    
    }
    
    public function setSamlIdentifier(string $identifier): self
    {
    
    }

    public function getSamlAttributes(): array 
    {
        // Retourne un tableau associatif des attributs SAML
        return [];
    }            

    public function setSamlAttributes(array $samlAttributes): void 
    {
        // Défini les attributs SAML
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
