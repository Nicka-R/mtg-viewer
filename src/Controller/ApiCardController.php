<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }
    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Numéro de page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', description: 'Nombre de cartes par page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 100))]
    #[OA\Get(description: 'Retourne toutes les cartes paginées')]
    #[OA\Response(response: 200, description: 'Liste paginée des cartes')]
    public function cardAll(Request $request, EntityManagerInterface $em): Response
    {
        ini_set('memory_limit', '512M');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, (int)$request->query->get('limit', 100));
        $offset = ($page - 1) * $limit;

        $repo = $em->getRepository(Card::class);
        $qb = $repo->createQueryBuilder('c')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $cards = $qb->getQuery()->getResult();

        // Pour retourner le nombre total de cartes (utile pour le front)
        $total = $repo->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'data' => $cards,
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit),
        ]);
    }

    #[Route('/search', name: 'Search cards', methods: ['GET'])]
    #[OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'setCode', description: 'Filtre par setCode', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Get(description: 'Recherche de cartes par nom et setCode')]
    #[OA\Response(response: 200, description: 'Résultats de la recherche')]
    public function cardSearch(Request $request, EntityManagerInterface $em): Response
    {
        $q = $request->query->get('q', '');
        $setCode = $request->query->get('setCode', null);

        if (strlen($q) < 3) {
            return $this->json([], 200);
        }

        $qb = $em->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->where('LOWER(c.name) LIKE :q')
            ->setParameter('q', '%' . strtolower($q) . '%')
            ->setMaxResults(20);

        if ($setCode) {
            $qb->andWhere('c.setCode = :setCode')
            ->setParameter('setCode', $setCode);
        }

        $cards = $qb->getQuery()->getResult();

        return $this->json($cards);
    }

    #[Route('/setcodes', name: 'List set codes', methods: ['GET'])]
    #[OA\Get(description: 'Liste tous les setCode disponibles')]
    #[OA\Response(response: 200, description: 'Liste des setCode')]
    public function listSetCodes(EntityManagerInterface $em): Response
    {
        $setCodes = $em->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->select('DISTINCT c.setCode')
            ->orderBy('c.setCode', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $this->json($setCodes);
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Put(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            return $this->json(['error' => 'Card not found'], 404);
        }
        return $this->json($card);
    }
}
