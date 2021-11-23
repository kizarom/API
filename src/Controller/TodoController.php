<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;


class TodoController extends AbstractFOSRestController

{
     /**
     * @var SerializerInterface
     */
    private $_serializer;
    /**
     * @var todoRepository
     */
    private $_todoRepository;
    /**
     * @var EntityManagerInterface
     */
    private $_entityManager;
     /**
     * UpdateTodoController constructor.
     * @param SerializerInterface $serializer
     * @param TodoRepository $todoRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SerializerInterface $serializer, TodoRepository $todoRepository, EntityManagerInterface $entityManager)
    {
        $this->_serializer = $serializer;
        $this->_todoRepository = $todoRepository;
        $this->_entityManager = $entityManager;
    }

//-------------------------------------afficher la liste des taches ---------------------------------

     /**
     * @Rest\Get("/api/todos", name="fos_getTodos")
     * @return JsonResponse
     */
    public function getTodos(): JsonResponse
    {
        // On récupère la liste des taches
        $todos = $this->_todoRepository->findAll();
        // On convertit en json
        $data = $this->_serializer->serialize($todos, 'json');
        // On envoie la réponse
        return JsonResponse::fromJsonString($data, Response::HTTP_OK);

    }

//---------------------------------------afficher une tache -----------------------------------------------

    /**
     * @Rest\Get("/api/todos/{id}", name="fos_getOneTodo")
     * @param $id
     * @return JsonResponse
     */
    public function getOneTodo($id): JsonResponse
    {
        // On récupère la taches
        $todo = $this->_todoRepository->find($id);
        if (!$todo) {
            return new JsonResponse(["Cette todo n'existe pas !"], Response::HTTP_NOT_FOUND, []);
        }
        // On convertit en json
        $response = $this->_serializer->serialize($todo, 'json');
        // On envoie la réponse
        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

//--------------------------------------------ajouter une tache ----------------------------------------------

    /**
     * @Rest\Post("/api/todos", name="fos_createtodo")
     * @param Request $request
     * @return JsonResponse
     */
    public function createTodo(Request $request)
    {
        // On décode les données envoyées
        $Data = json_decode($request->getContent(), true);

        $todo = $this->_todoRepository->findOneBy([
            'title' => $Data['title']
        ]);

        if (!is_null($todo)) {
            return new JsonResponse('Ce todo existe déjà', Response::HTTP_CONFLICT);
        }
        // On instancie une nouvel tache
        $todo = new Todo();
        // On hydrate l'objet
        $todo->setTitle($Data['title']);
        // On sauvegarde en base
        $this->_entityManager->persist($todo);
        $this->_entityManager->flush();
        // On retourne la confirmation
        return new JsonResponse([], Response::HTTP_OK);
    }

//--------------------------------------modifier une tache -----------------------------------------------------

    /**
     * @Rest\Put("/api/todos/{id}", name="fos_updateTodo")
     * @param $id
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function updateTodo($id, Request $request)
    {
        $todo = $this->_todoRepository->find($id);
        if (!$todo) {
            return new JsonResponse(["Cette todo n'existe pas !"], 404, []);
        }
        // On décode les données envoyées
        $data = json_decode($request->getContent(), true);
        // On hydrate l'objet
        $todo->setTitle($data['title']);
        // On sauvegarde en base
        $this->_entityManager->flush();
        // On convertit en json
        $response = $this->_serializer->serialize($todo, 'json');
        // On envoie la réponse
        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }


//----------------------------------------supprimer une tache ---------------------------------------------------

     /**
     * @Rest\Delete("/api/todos/{id}", name="fos_deleteTodo")
     * @param $id
     * @return JsonResponse
     */
    public function deleteTodo($id): JsonResponse
    {
        $todo = $this->_todoRepository->find($id);
        if (!$todo) {
            return new JsonResponse(["Cette todo n'existe pas !"], Response::HTTP_NOT_FOUND, []);
        }
        //suppression de données
        $this->_entityManager->remove($todo);
        $this->_entityManager->flush();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

}
