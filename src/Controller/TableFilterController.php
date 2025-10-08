<?php

namespace App\Controller;

use App\Entity\TableFilter;
use App\Repository\TableFilterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/table-filter')]
class TableFilterController extends AbstractController
{
    public function __construct(
        private TableFilterRepository $tableFilterRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    #[Route('/save', name: 'app_table_filter_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name']) || !isset($data['tableId']) || !isset($data['configuration'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->security->getUser();
        
        // Creación del filtro
        $filter = new TableFilter();
        $filter->setName($data['name']);
        $filter->setTableId($data['tableId']);
        $filter->setConfiguration($data['configuration']);
        $filter->setUser($user);
        $filter->setIsPublic($data['isPublic'] ?? false);
        
        $this->entityManager->persist($filter);
        $this->entityManager->flush();
        
        return $this->json(['success' => true, 'id' => $filter->getId()]);
    }
    
    #[Route('/{id}', name: 'app_table_filter_get', methods: ['GET'])]
    public function get(TableFilter $filter): JsonResponse
    {
        $user = $this->security->getUser();
        
        if ($filter->getUser() !== $user && !$filter->isPublic()) {
            return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        return $this->json([
            'success' => true,
            'id' => $filter->getId(),
            'name' => $filter->getName(),
            'tableId' => $filter->getTableId(),
            'configuration' => $filter->getConfiguration(),
            'isPublic' => $filter->isPublic(),
            'createdAt' => $filter->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }
    
    #[Route('/by-table/{tableId}', name: 'app_table_filter_by_table', methods: ['GET'])]
    public function getByTable(string $tableId): JsonResponse
    {
        /** @var User $user  */
        $user = $this->security->getUser();
        
        // Obtener filtros del usuario actual
        $userFilters = $this->tableFilterRepository->findByTableIdAndUser($tableId, $user->getId());

        // Obtener filtros públicos
        $publicFilters = $this->tableFilterRepository->findPublicByTableId($tableId);
        
        // Formatear los resultados
        $formatFilters = function($filters) {
            return array_map(function($filter) {
                return [
                    'id' => $filter->getId(),
                    'name' => $filter->getName(),
                    'createdAt' => $filter->getCreatedAt()->format('Y-m-d H:i:s'),
                    'userName' => $filter->getUser()->getName()
                ];
            }, $filters);
        };
        
        return $this->json([
            'success' => true,
            'userFilters' => $formatFilters($userFilters),
            'publicFilters' => $formatFilters($publicFilters)
        ]);
    }

    #[Route('/update/{id}', name: 'app_table_filter_update', methods: ['PUT'])]
    public function update(Request $request, TableFilter $filter): JsonResponse
    {
        $user = $this->security->getUser();
        
        if ($filter->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }
        
        $filter->setName($data['name']);
        
        if (isset($data['isPublic'])) {
            $filter->setIsPublic($data['isPublic']);
        }

        // Actualizar la última fecha de modificación
        $filter->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/{id}', name: 'app_table_filter_delete', methods: ['DELETE'])]
    public function delete(TableFilter $filter): JsonResponse
    {
        $user = $this->security->getUser();
        
        if ($filter->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        $this->entityManager->remove($filter);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }
}