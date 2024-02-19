<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\IdpMetadataServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshIdpMetadataCommand extends Command
{
    protected static $defaultDescription = 'Refresh the IdP metadata from the configured File, String or URL';

    public function __construct(
        protected IdpMetadataServiceInterface $idpMetadataService,
        protected ConfigurationServiceInterface $configurationService,
    ) {
        parent::__construct('umanit:saml:refresh-idp-metadata');
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command allows you to refresh the IdP metadata from the configured File, String or URL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $providers = $this->configurationService->getProviderNames();

        foreach ($providers as $provider) {
            $io->comment(sprintf('Refreshing metadata for provider "%s"', $provider));

            $this->idpMetadataService->clearCache($provider);
            $entityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);

            $io->success(sprintf('Metadata refreshed %s => %s.', $provider, $entityDescriptor->getEntityID()));
        }

        return Command::SUCCESS;
    }
}
