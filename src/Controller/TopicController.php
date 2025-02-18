<?php
namespace App\Controller;

use App\Entity\Topic;
use App\Form\TopicType;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/topic')]
class TopicController extends AbstractController
{
    #[Route('/', name: 'app_topic_index', methods: ['GET'])]
    public function index(TopicRepository $topicRepository): Response
    {
        return $this->render('topic/index.html.twig', [
            'topics' => $topicRepository->findAllOrderedByDate(),
        ]);
    }

    #[Route('/new', name: 'app_topic_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $topic = new Topic();
        $topic->setAuthor($this->getUser());
        
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($topic);
            $entityManager->flush();

            return $this->redirectToRoute('app_topic_index');
        }

        return $this->render('topic/new.html.twig', [
            'topic' => $topic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_topic_show', methods: ['GET'])]
    public function show(Topic $topic): Response
    {
        return $this->render('topic/show.html.twig', [
            'topic' => $topic,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_topic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur ou admin
        if ($this->getUser() !== $topic->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        return $this->render('topic/edit.html.twig', [
            'topic' => $topic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_topic_delete', methods: ['POST'])]
    public function delete(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur ou admin
        if ($this->getUser() !== $topic->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if (!$topic->getComments()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer un sujet qui contient des commentaires');
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$topic->getId(), $request->request->get('_token'))) {
            $entityManager->remove($topic);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_topic_index');
    }

    #[Route('/{id}/like', name: 'app_topic_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function like(Topic $topic, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'auteur ne peut pas liker son propre sujet
        if ($this->getUser() === $topic->getAuthor()) {
            return $this->json(['error' => 'Vous ne pouvez pas liker votre propre sujet'], 403);
        }

        $topic->setLikes($topic->getLikes() + 1);
        $entityManager->flush();

        return $this->json(['likes' => $topic->getLikes()]);
    }
}