<?php

namespace App\Controller\Admin;
use App\Entity\Message; 

use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; 




class MessageController extends AbstractController
{
    #[Route('/admin/messages', name: 'list-messages', methods: ['GET', 'POST'])]
    public function listMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findAll();

        return $this->render('admin/messages/list-messages.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/admin/messages/delete/{id}', name: 'delete-message', methods: ['POST'])]
    public function deleteMessage(int $id, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        $entityManager->remove($message);
        $entityManager->flush();

        return $this->redirectToRoute('admin-messages-list-messages');
    }

    #[Route('/admin/messages/view/{id}', name: 'admin-messages-view', methods: ['GET', 'POST'])]
    public function viewMessage(int $id, MessageRepository $messageRepository): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        return $this->render('admin/messages/view.html.twig', [
            'message' => $message
        ]);
    }

    
}