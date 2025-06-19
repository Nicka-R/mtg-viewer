<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Add a short description for your command',
)]
class ImportCardCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private array $csvHeader = []
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Importe les cartes depuis le CSV')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Nombre maximum de cartes à importer', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';

        $this->logger->info('Début de l\'import des cartes', ['file' => $filepath]);
        $start = microtime(true);

        try {
            $handle = fopen($filepath, 'r');
            $this->logger->info('Importing cards from ' . $filepath);

            if ($handle === false) {
                $this->logger->error('Fichier non trouvé', ['file' => $filepath]);
                $io->error('File not found');
                return Command::FAILURE;
            }

            $i = 0;
            $this->csvHeader = fgetcsv($handle);
            $uuidInDatabase = $this->entityManager->getRepository(Card::class)->getAllUuids();

            $progressIndicator = new ProgressIndicator($output);
            $progressIndicator->start('Importing cards...');

            $limit = $input->getOption('limit');
            if ($limit !== null) {
                $limit = (int)$limit;
            }

            while (($row = $this->readCSV($handle)) !== false) {
                $i++;

                if (!in_array($row['uuid'], $uuidInDatabase)) {
                    $this->addCard($row);
                }

                if ($i % 2000 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $progressIndicator->advance();
                }

                // Arrête la boucle si la limite est atteinte
                if ($limit !== null && $i >= $limit) {
                    break;
                }
            }
            // Toujours flush en sortie de boucle
            $this->entityManager->flush();
            $progressIndicator->finish('Importing cards done.');

            fclose($handle);

            $end = microtime(true);
            $timeElapsed = $end - $start;

            $this->logger->info('Fin de l\'import des cartes', [
                'file' => $filepath,
                'cartes_importées' => $i,
                'durée' => round($timeElapsed, 2) . 's'
            ]);

            $io->success(sprintf('Imported %d cards in %.2f seconds', $i, $timeElapsed));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'import', [
                'exception' => $e,
                'file' => $filepath
            ]);
            $io->error('Erreur lors de l\'import : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }


    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($this->csvHeader, $row);
    }

    private function addCard(array $row)
    {
        $uuid = $row['uuid'];

        $card = new Card();
        $card->setUuid($uuid);
        $card->setManaValue($row['manaValue']);
        $card->setManaCost($row['manaCost']);
        $card->setName($row['name']);
        $card->setRarity($row['rarity']);
        $card->setSetCode($row['setCode']);
        $card->setSubtype($row['subtypes']);
        $card->setText($row['text']);
        $card->setType($row['type']);
        $this->entityManager->persist($card);
    }
}
