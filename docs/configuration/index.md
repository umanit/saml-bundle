# Configuration

## Référence de configuration

```bash
# Default configuration for extension with alias: "umanit_saml"
umanit_saml:
    # Chemin de stockage des certificats
    certificat_path:      '%kernel.project_dir%/certs' 
    # Liste des fournisseurs SAML
    providers:
        # Prototype
        provider_name:
            # Activer ou non le provider
            enabled:              true
            # Activer ou non le mode strict
            strict:               true
            # Type de SAML à utiliser (SP Initiated ; IdP Initiated)
            # One of :
            # Umanit\SamlBundle\Enums\Mode::SP_INITIATED <- Default
            # Umanit\SamlBundle\Enums\Mode::IDP_INITIATED
            type: !php/const Umanit\SamlBundle\Enums\Mode::SP_INITIATED 
            # Service provider configuration
            sp:
                # Entity id
                entity_id:            ~
                # Metadata URL, File or XML string
                metadata:             ~
                # Metadata cache duration in seconds
                metadata_cache_duration: 3600
                # NameIDFormat
                name_id_format: 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
                # Assertion Consumer Service
                acs:
                    url:                  ~
                    route:                umanit_saml_acs
                    binding:              ~
                # Single Logout Service
                slo:
                    url:                  ~
                    route:                umanit_saml_slo
                    binding:              ~
                # X509 Certificat
                x509cert:             ~
                # Private Key
                private_key:
                    # Path to private Key
                    path:                 ~
                    # Type de chiffrement
                    # One of :
                    # Umanit\SamlBundle\Enums\Encryption::TRIPLEDES_CBC 
                    # Umanit\SamlBundle\Enums\Encryption::AES128_CBC 
                    # Umanit\SamlBundle\Enums\Encryption::AES192_CBC 
                    # Umanit\SamlBundle\Enums\Encryption::AES256_CBC 
                    # Umanit\SamlBundle\Enums\Encryption::AES128_GCM 
                    # Umanit\SamlBundle\Enums\Encryption::AES192_GCM 
                    # Umanit\SamlBundle\Enums\Encryption::AES256_GCM 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_1_5 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_OAEP_MGF1P 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_OAEP 
                    # Umanit\SamlBundle\Enums\Encryption::DSA_SHA1 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA1 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA256 <- Default
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA384 
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA512 
                    # Umanit\SamlBundle\Enums\Encryption::HMAC_SHA1
                    encryption: !php/const Umanit\SamlBundle\Enums\Encryption::RSA_SHA256 
                # Private Key Passphrase
                private_key_passphrase: ~
                # Type de chiffrement
                # One of :
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::XMLDSIGNS 
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA1
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA256 <- Default
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA384 
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA512 
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::RIPEMD160
                saml_algorithm_signature: !php/const Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA256 
            # Identity provider configuration
            idp:
                # Entity id, by default the first of the metadata
                entity_id:            ~
                # Metadata URL, File or XML string
                metadata:             ~
                # Metadata cache duration in seconds
                metadata_cache_duration: 3600
                # NameIDFormat
                name_id_format:       'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
                # Single Sign On Service
                sso:
                    url:                  ~
                    route:                umanit_saml_sso
                    binding:              ~
                # Single Logout Service
                slo:
                    url:                  ~
                    route:                umanit_saml_slo
                    binding:              ~
                # X509 Certificat
                x509cert:             ~
                # Private Key
                private_key:
                    # Path to private Key
                    path:                 ~
                    # Type de chiffrement
                    # One of :
                    # Umanit\SamlBundle\Enums\Encryption::TRIPLEDES_CBC
                    # Umanit\SamlBundle\Enums\Encryption::AES128_CBC
                    # Umanit\SamlBundle\Enums\Encryption::AES192_CBC
                    # Umanit\SamlBundle\Enums\Encryption::AES256_CBC
                    # Umanit\SamlBundle\Enums\Encryption::AES128_GCM
                    # Umanit\SamlBundle\Enums\Encryption::AES192_GCM
                    # Umanit\SamlBundle\Enums\Encryption::AES256_GCM
                    # Umanit\SamlBundle\Enums\Encryption::RSA_1_5
                    # Umanit\SamlBundle\Enums\Encryption::RSA_OAEP_MGF1P
                    # Umanit\SamlBundle\Enums\Encryption::RSA_OAEP
                    # Umanit\SamlBundle\Enums\Encryption::DSA_SHA1
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA1
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA256 <- Default
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA384
                    # Umanit\SamlBundle\Enums\Encryption::RSA_SHA512
                    # Umanit\SamlBundle\Enums\Encryption::HMAC_SHA1
                    encryption: !php/const Umanit\SamlBundle\Enums\Encryption::RSA_SHA256 
                # Private Key Passphrase
                private_key_passphrase: ~
                # Type de chiffrement
                # One of :
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::XMLDSIGNS
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA1
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA256 <- Default
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA384
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA512
                # Umanit\SamlBundle\Enums\SamlEncryptionSignature::RIPEMD160
                saml_algorithm_signature: !php/const Umanit\SamlBundle\Enums\SamlEncryptionSignature::SHA256 
```

## Sp inititated minimal configuration

```yaml
umanit_saml:
    certificat_path: '%kernel.project_dir%/certs'
    providers:
        # App (SP) -> demande d'indentité vers Onelogin (IDP) -> renvoi de l'identité App (SP)
        onelogin:
            sp:
                # Doit respecter le format (Unique identifiant de l'application)
                entity_id: 'https://suivi-temps.wip/saml2/onelogin'
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
                metadata: 'https://app.onelogin.com/saml/metadata/35f857e9-cfcc-42f6-a9f1-e850cabebff1'
``` 

## Idp inititated minimal configuration

```yaml
        #IDP: Application who send the identity response
        app_a:
            type: !php/enum Umanit\SamlBundle\Enums\Mode::IDP_INITIATED
            sp:
                metadata: 'https://saml-bundle.wip/saml2/metadata/app_b'
            idp:
                entity_id: https://saml-bundle.wip/saml2/app_a
                name_id_format: !php/const LightSaml\SamlConstants::NAME_ID_FORMAT_EMAIL
                x509cert: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.crt'
                private_key:
                    path: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.key'
                    
        #SP: Application who receive the identity response
        app_b:
            type: !php/enum Umanit\SamlBundle\Enums\Mode::SP_INITIATED
            sp:
                entity_id: 'https://saml-bundle.wip/saml2/app_b'
                name_id_format: !php/const LightSaml\SamlConstants::NAME_ID_FORMAT_EMAIL
                x509cert: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.crt'
                private_key:
                    path: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.key'
            idp:
                metadata: 'https://saml-bundle.wip/saml2/metadata/app_a'
```
