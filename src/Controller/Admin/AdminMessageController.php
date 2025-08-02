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


    #[Route('/admin/messages/list-messages', name: 'admin-list-messages', methods: ['GET'])]
    public function listMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/messages/list-messages.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/admin/messages/delete/{id}', name: 'admin-delete-message', methods: ['POST'])]
    public function deleteMessage(int $id, MessageRepository $messageRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        //CSRF Protection 
        if (!$this->isCsrfTokenValid('delete_message_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin-list-messages');
        }

        $message = $messageRepository->find($id);

        if (!$message) {
            $this->addFlash('error', 'Message introuvable');
            return $this->redirectToRoute('admin-list-messages');
        }

        try {
            $entityManager->remove($message);
            $entityManager->flush();

            $this->addFlash('success', 'Message supprimé avec succès !');
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Impossible de supprimer le message');
        }

        return $this->redirectToRoute('admin-list-messages');
    }


    #[Route('/admin/messages/update/{id}', name: 'admin-update-message', methods: ['GET', 'POST'])]
    public function updateMessage(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            $this->addFlash('error', 'Message introuvable');
            return $this->redirectToRoute('admin-list-messages');
        }

        $form = $this->createForm(MessageTypeForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Message modifié avec succès !');
                return $this->redirectToRoute('admin-list-messages');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Erreur lors de la modification');
            }
        }

        return $this->render('admin/messages/update-message.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }
}
