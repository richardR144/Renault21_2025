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
    #[Route('/messages', name: 'list-messages', methods: ['GET'])]
    public function listMessages(MessageRepository $messageRepository, UserRepository $userRepository): Response {
        $message = $messageRepository->findAll();
        $user = $this->getUser();
        return $this->render('guest/messages/list-messages.html.twig', [
            'messages' => $message
        ]);
    }

    #[Route('/messages/view/{id}', name: 'view', methods: ['GET'])]
    public function viewMessage(int $id, MessageRepository $messageRepository, UserRepository $userRepository): Response {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        return $this->render('guest/messages/view.html.twig', [
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

    #[Route('/messages/create', name: 'create-message', methods: ['GET'])]
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



