# Configuration

```yaml
umanit_saml:
    certificat_path: '%kernel.project_dir%/certs'
    providers:
        microsoft_umanit_provider:
            type: !php/enum Umanit\SamlBundle\Enums\Mode::SP_INITIATED
            # type: !php/const Umanit\SamlBundle\Enums\Mode::IDP_INITIATED
            sp:
                # entity_id: https://saml-bundle.wip/saml2/microsoft_umanit_provider
                entity_id: wvipWPxLrw24oQ6Xzo6sZh94n7N9d6
                acs:
                    route: 'umanit_saml_acs'
                    # url: https://saml-bundle.wip/saml2/acs/microsoft_umanit_provider
                    binding: !php/const LightSaml\SamlConstants::BINDING_SAML2_HTTP_POST
                slo:
                    route: 'umanit_saml_slo'
                    # url: https://saml-bundle.wip/saml2/slo/microsoft_umanit_provider
                    # binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST
                # Certificat X509
                x509cert: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.crt'
                # Clé privée
                private_key: '%kernel.project_dir%/certs/microsoft_umanit_provider/saml.key'
            idp:
                # Optional automatic detection if not set (from metadata)
                # entity_id: ''

                # Metadata URL, File or XML string
                metadata: 'https://login.microsoftonline.com/9d21d004-0c5d-4069-bf84-4c799d627d43/FederationMetadata/2007-06/FederationMetadata.xml'

                # Metadata cache duration in seconds
                # metadata_cache_duration: 86400

                # sso:
                #     url: https://login.microsoftonline.com/9d21d004-0c5d-4069-bf84-4c799d627d43/saml2
                #     binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
                # slo:
                #     url: https://login.microsoftonline.com/9d21d004-0c5d-4069-bf84-4c799d627d43/saml2
                #     binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
                # x509cert: microsoft_umanit_provider/saml/saml.crt
```
