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
use Symfony\Component\Security\Http\Attribute\IsGranted;
// Contrôleur pour la gestion des messages des utilisateurs invités (guest)

class MessagesController extends AbstractController {
    #[Route('/guest/messages/list-messages', name: 'list-messages', methods: ['GET', 'POST'])]
    public function listMessages(MessageRepository $messageRepository, UserRepository $userRepository): Response {
        $user = $this->getUser();
        $messages = $messageRepository->findBy(['receiver' => $user], ['createdAt' => 'DESC']);
         
        return $this->render('guest/messages/list-messages.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/messages/read/{id}', name: 'read-message', methods: ['GET', 'POST'])]
    public function readMessage(int $id, Request $request, MessageRepository $messageRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response {
    $message = $messageRepository->find($id);

    if (!$message) {
        throw $this->createNotFoundException('Message non trouvé');
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

    #[Route('/messages/delete/{id}', name: 'delete-message', methods: ['POST'])]
    public function deleteMessage(int $id, MessageRepository $messageRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        $entityManager->remove($message);
        $entityManager->flush();

        return $this->redirectToRoute('list-messages');
    }

    #[Route('/messages/create', name: 'create-message', methods: ['GET', 'POST'])]
    public function createMessage(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response {
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
    public function updateMessage(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $message->setContent($content);
            $entityManager->persist($message);
            $entityManager->flush();    

            return $this->redirectToRoute('list-messages');
        }

        return $this->render('guest/messages/update-message.html.twig', [
            'message' => $message
        ]);
    }
}



