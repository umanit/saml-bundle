<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Umanit\SamlBundle\Service\ConfigurationServiceInterface;
use Umanit\SamlBundle\Service\MetadataServiceInterface;

#[AsCommand(
    name: 'umanit:saml:refresh-idp-metadata',
    description: 'Refresh the IdP metadata from the configured File, String or URL',
)]
class RefreshIdpMetadataCommand extends Command
{
    public function __construct(
        protected MetadataServiceInterface $idpMetadataService,
        protected ConfigurationServiceInterface $configurationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to refresh the IdP metadata from the configured File, String or URL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $providers = $this->configurationService->getProviderNames();

        foreach ($providers as $provider) {
            $io->comment(\sprintf('Refreshing metadata for provider "%s"', $provider));

            try {
                $this->idpMetadataService->clearCache($provider);
                $entityDescriptor = $this->idpMetadataService->getEntityDescriptor($provider);

                $io->success(\sprintf('Metadata refreshed %s => %s.', $provider, $entityDescriptor->getEntityID()));
            } catch (\Throwable $e) {
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
