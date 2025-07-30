<?php

namespace App\Controller\Guest;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Contrôleur pour la gestion des messages des utilisateurs invités (guest)


class MessagesController extends AbstractController
{
    #[Route('/guest/messages/list-messages', name: 'list-messages', methods: ['GET', 'POST'])]
    public function listMessages(MessageRepository $messageRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $messages = $messageRepository->findBy(['receiver' => $user], ['createdAt' => 'DESC']);

        return $this->render('guest/messages/list-messages.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/messages/read/{id}', name: 'read-message', methods: ['GET', 'POST'])]
    public function readMessage(int $id, Request $request, MessageRepository $messageRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $message = $messageRepository->find($id);

        if (!$message || ($message->getSender() !== $this->getUser() && $message->getReceiver() !== $this->getUser())) {
            throw $this->createNotFoundException('Message non trouvé ou accès non autorisé');
        }

        if ($request->isMethod('POST')) {
            $isRead = $request->request->get('isRead', 0);
            $message->setIsRead($isRead ? true : false);
            $entityManager->flush();
            return $this->redirectToRoute('list-messages');
        }

        // Ne pas modifier le statut en GET !
        return $this->render('guest/messages/read-message.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/messages/delete/{id}', name: 'delete-message', methods: ['GET', 'POST'])]
    public function deleteMessage(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        //SÉCURISATION AJOUTÉE
        $this->denyAccessUnlessGranted('ROLE_USER');

        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        // VÉRIFICATION DE PROPRIÉTÉ
        if ($message->getSender() !== $this->getUser() && $message->getReceiver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce message');
        }

        // Si GET = afficher confirmation, si POST = supprimer
        if ($request->isMethod('POST')) {
            $entityManager->remove($message);
            $entityManager->flush();

            $this->addFlash('success', 'Message supprimé avec succès');
            return $this->redirectToRoute('list-messages');
        }

        // GET = page de confirmation
        return $this->render('guest/messages/delete-message.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/messages/create', name: 'create-message', methods: ['GET', 'POST'])]
    public function createMessage(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $sender = $this->getUser();
            $receiverId = $request->request->get('receiver_id');
            $receiver = $userRepository->find($receiverId);

            if (!$receiver) {
                throw $this->createNotFoundException('Destinataire non trouvé');
            }

            $message = new Message();
            $message->setContent($content);
            $message->setSender($sender);
            $message->setReceiver($receiver);
            $message->setCreatedAt(new \DateTime('now'));

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('list-messages');
        }

        return $this->render('guest/messages/create-message.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/messages/update/{id}', name: 'update-message', methods: ['GET', 'POST'])]
    public function updateMessage(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        //SÉCURISATION AJOUTÉE
        $this->denyAccessUnlessGranted('ROLE_USER');

        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        // VÉRIFICATION DE PROPRIÉTÉ - Seul l'expéditeur peut modifier
        if ($message->getSender() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres messages');
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            if (empty(trim($content))) {
                $this->addFlash('error', 'Le contenu du message ne peut pas être vide');
                return $this->render('guest/messages/update-message.html.twig', [
                    'message' => $message
                ]);
            }

            $message->setContent($content);
            $message->setUpdatedAt(new \DateTime()); // Ajouter timestamp de modification
            $entityManager->flush();

            $this->addFlash('success', 'Message modifié avec succès');
            return $this->redirectToRoute('list-messages');
        }

        return $this->render('guest/messages/update-message.html.twig', [
            'message' => $message
        ]);
    }
}
