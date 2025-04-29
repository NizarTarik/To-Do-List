<?php

namespace App\Controller;

use App\Entity\Tasks;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TasksController extends AbstractController
{
    // Index Route
    #[Route('/', name: 'tasks_index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $taskRepository = $entityManager->getRepository(Tasks::class);

        $tasks = $taskRepository->findAll();

        return $this->render('tasks/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    // Create Route
    #[Route('/tasks/create', name: 'tasks_create')]
    public function create(Request $request): Response
    {
        // Check if there is any response message passed through the query string
        $response = $request->query->get('response');
        $message = $request->query->get('message');

        return $this->render('tasks/create.html.twig', [
            'response' => $response,
            'message' => $message,
        ]);
    }


    // Store Route
    #[Route('/tasks/store', name: 'tasks_store')]
    public function store(Request $request, ManagerRegistry $doctrine): RedirectResponse
    {
        $entityManager = $doctrine->getManager();

        $name = $request->request->get('name');
        $date = new \DateTime($request->request->get('date'));

        $image = $request->files->get('image');
        if ($image) {
            // Validate image extension
            $allowedExtensions = ['png', 'jpeg', 'jpg'];
            $extension = $image->guessExtension();
            if (!in_array($extension, $allowedExtensions)) {
                return $this->redirectToRoute('tasks_create', [
                    'response' => 'error',
                    'message' => 'Invalid image format. Only PNG, JPEG, and JPG are allowed.',
                ]);
            }
            // Generate a unique filename for the image
            $imageName = uniqid() . '.' . $extension;
            // Move the file to your folder
            $image->move(
                'uploads/images/',
                $imageName
            );

            // Image path
            $imagePath = 'uploads/images/' . $imageName;


            $task = new Tasks();
            $task->setName($name);
            $task->setDate($date);
            $task->setTaskImage($imagePath);
        }


        $entityManager->persist($task);
        $entityManager->flush();

        return $this->redirectToRoute('tasks_index', [
            'response' => 'ok',
            'message' => 'Task created successfully!',
        ]);
    }


    // Delete Route
    #[Route('/tasks/delete/{id}', name: 'tasks_delete', methods: ['POST'])]
    public function delete($id, ManagerRegistry $doctrine): RedirectResponse
    {
        $entityManager = $doctrine->getManager();
        $taskRepository = $entityManager->getRepository(Tasks::class);

        $task = $taskRepository->find($id);

        if (!$task) {
            $this->addFlash('error', 'Task not found.');
            return $this->redirectToRoute('tasks_index');
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->redirectToRoute('tasks_index');
    }


    // Edit Route
    #[Route('/tasks/edit/{id}', name: 'tasks_edit')]
    public function edit($id, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $taskRepository = $entityManager->getRepository(Tasks::class);

        $task = $taskRepository->find($id);

        if (!$task) {

            return $this->redirectToRoute('tasks_index');
        }

        return $this->render('tasks/edit.html.twig', [
            'task' => $task,
        ]);
    }

    // Update Route
    #[Route('/tasks/update/{id}', name: 'tasks_update', methods: ['POST'])]
    public function update($id, Request $request, ManagerRegistry $doctrine): RedirectResponse
    {
        $entityManager = $doctrine->getManager();
        $taskRepository = $entityManager->getRepository(Tasks::class);

        $task = $taskRepository->find($id);

        // Task not found
        if (!$task) {
            $this->addFlash('error', 'Task not found.');
            return $this->redirectToRoute('tasks_index');
        }

        $task->setName($request->request->get('name'));
        $task->setDate(new \DateTime($request->request->get('date')));

        $entityManager->flush();


        return $this->redirectToRoute('tasks_index');
    }
}
