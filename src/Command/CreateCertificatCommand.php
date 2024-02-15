<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CreateCertificatCommand extends Command
{
    protected string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
        parent::__construct('umanit:saml:create-certificat');
    }

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Create a new X509 certificate and private key')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to create a new X509 certificate and private key')
            ->addArgument('name', InputOption::VALUE_REQUIRED, 'Name of the certificate')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Number of days the certificate is valid', 365)
            ->addOption('keyname', null, InputOption::VALUE_OPTIONAL, 'Name of the private key file', 'saml.key')
            ->addOption('certname', null, InputOption::VALUE_OPTIONAL, 'Name of the certificate file', 'saml.crt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        if (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '.')) {
            throw new \RuntimeException('The name of the certificate cannot contain a slash or a dot.');
        }

        $days = $input->getOption('days');
        $keyname = $input->getOption('keyname') ?? $name;
        $certname = $input->getOption('certname') ?? $name;

        if (!str_ends_with($keyname, '.key')) {
            $keyname .= '.key';
        }

        if (!str_ends_with($certname, '.crt')) {
            $certname .= '.crt';
        }

        $storagePath = $this->storagePath . DIRECTORY_SEPARATOR . $input->getArgument('name');

        if (!is_dir($storagePath) &&
            !mkdir($concurrentDirectory = $storagePath, 0755, true) &&
            !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        $keyStoragePath = $storagePath . DIRECTORY_SEPARATOR . $keyname;
        $certStoragePath = $storagePath . DIRECTORY_SEPARATOR . $certname;

        $io = new SymfonyStyle($input, $output);
        $io->title('Creating X509 certificate and private key');
        $io->text('The certificate will be valid for ' . $days . ' days');
        $io->text('The private key will be stored in ' . $keyStoragePath);
        $io->text('The certificate will be stored in ' . $certStoragePath);
        $io->newLine();

        $question = 'The name chosen for the PEM files already exist. Would you like to overwrite existing PEM files?';
        $filesNotExists = !file_exists($keyStoragePath) && !file_exists($certStoragePath);

        if ($filesNotExists || $io->confirm($question)) {
            $process = new Process([
                'openssl', 'req', '-x509', '-nodes',
                '-days', $days,
                '-newkey', 'rsa:2048',
                '-keyout', $keyStoragePath,
                '-out', $certStoragePath
            ]);
            $process->setTty(true);
            $process->run();

            return Command::SUCCESS;
        }

        $io->error('The certificate or the private key already exists');
        return Command::FAILURE;
    }
}
