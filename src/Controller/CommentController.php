<?php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Topic;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/topic/{id}/new', name: 'app_comment_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setAuthor($this->getUser());
        $comment->setTopic($topic);
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté');
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur ou admin
        if ($this->getUser() !== $comment->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire modifié avec succès');
            return $this->redirectToRoute('app_topic_show', ['id' => $comment->getTopic()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur ou admin
        if ($this->getUser() !== $comment->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire supprimé');
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $comment->getTopic()->getId()]);
    }

    #[Route('/{id}/report', name: 'app_comment_report', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function report(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setIsReported(true);
        $entityManager->flush();
        
        $this->addFlash('success', 'Commentaire signalé pour modération');
        return $this->redirectToRoute('app_topic_show', ['id' => $comment->getTopic()->getId()]);
    }

    #[Route('/{id}/validate', name: 'app_comment_validate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function validate(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setIsValidated(true);
        $comment->setIsReported(false);
        $entityManager->flush();
        
        $this->addFlash('success', 'Commentaire validé');
        return $this->redirectToRoute('app_topic_show', ['id' => $comment->getTopic()->getId()]);
    }
}