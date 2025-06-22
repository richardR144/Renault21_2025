<?php

namespace App\Controller\Admin;
use App\Entity\Message; 
use App\Form\MessageTypeForm;

use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminMessageController extends AbstractController
{

    #[Route('/admin/messages/create-message', name: 'admin-create-message', methods: ['GET', 'POST'])]
    public function createMessage(Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageTypeForm::class, $message);
        

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($this->getUser());
            $message->setCreatedAt(new \DateTime());
            $message->setIsRead(false);
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('admin-list-messages');
        }

        return $this->render('admin/messages/create-message.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/admin/messages/list-messages', name: 'admin-list-messages', methods: ['GET', 'POST'])]
    public function listMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findAll();

        return $this->render('admin/messages/list-messages.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/admin/messages/delete/{id}', name: 'admin-delete-message', methods: ['POST'])]
    public function deleteMessage(int $id, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        $entityManager->remove($message);
        $entityManager->flush();

        return $this->redirectToRoute('admin-list-messages');
    }

    #[Route('/admin/messages/update/{id}', name: 'admin-update-message', methods: ['GET', 'POST'])]
    public function updateMessage(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        $form = $this->createForm(MessageTypeForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('admin-list-messages');
        }

        return $this->render('admin/messages/update-message.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }

    
}