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
    #[Route('/admin/messages', name: 'admin-messages-list', methods: ['GET'])]
    public function listMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findAll();

        return $this->render('admin/messages/list.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/admin/messages/delete/{id}', name: 'admin-messages-delete', methods: ['POST'])]
    public function deleteMessage(int $id, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message not found');
        }

        $entityManager->remove($message);
        $entityManager->flush();

        return $this->redirectToRoute('admin-messages-list');
    }

    #[Route('/admin/messages/view/{id}', name: 'admin-messages-view', methods: ['GET'])]
    public function viewMessage(int $id, MessageRepository $messageRepository): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message not found');
        }

        return $this->render('admin/messages/view.html.twig', [
            'message' => $message
        ]);
    }

    
}