<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/post/', name: 'post_')]
class PostController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('index', name: 'index')]
    public function index(): Response
    {
        $emPost = $this->entityManager->getRepository(Post::class);

        $result = [];
        if($this->getUser()) {
            $result['last_posts_user'] = $emPost->findLastPostsByUser($this->getUser()->getId());
            $result['top_posts_user'] = $emPost->findTopPostsByUser($this->getUser()->getId());
        }

        $result['posts'] = $emPost->findAll();
        $result['last_posts'] = $emPost->findLastPosts();

        return $this->render('post/index.html.twig', $result);
    }

    #[Route('add', name: 'add')]
    public function add(Request $request): Response
    {
        $this->isGranted('IS_AUTHENTICATED_FULLY');
        $emPost = $this->entityManager->getRepository(Post::class);

        $form = $this->createForm(PostType::class, new Post)
            ->remove('isDeleted')
            ->remove('isPublished')
            ->remove('author');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setAuthor($this->getUser());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Article crée');
            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'last_posts_user' => $emPost->findLastPostsByUser($this->getUser()->getId()),
            'top_posts_user' => $emPost->findTopPostsByUser($this->getUser()->getId())
        ]);
    }

    #[Route('{post}', name: 'read')]
    public function post(Post $post, Request $request): Response
    {
        $emPost = $this->entityManager->getRepository(Post::class);

        $comment = (new Comment())
                ->setPost($post)
                ->setAuthor(($this->getUser()));

        $form = $this->createForm(CommentType::class, $comment)
            ->remove('createdAt')
            ->remove('author')
            ->remove('post');

        if($this->getUser()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $comment = $form->getData();

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                return $this->redirectToRoute('post_read', ['post' => $post->getId()]);
            }
        }

        $result = [
            'form' => $form->createView(),
            'post' => $post
        ];

        if($this->getUser()) {
            $result['last_posts_user'] = $emPost->findLastPostsByUser($this->getUser()->getId());
            $result['top_posts_user'] = $emPost->findTopPostsByUser($this->getUser()->getId());
        }

        return $this->render('post/post.html.twig', $result);
    }

    #[Route('edit/{post}', name: 'edit')]
    public function edit(Post $post, Request $request) {
        $this->denyAccessUnlessGranted('edit', $post);

        $emPost = $this->entityManager->getRepository(Post::class);

        $form = $this->createForm(PostType::class, $post)
            ->remove('isDeleted')
            ->remove('isPublished')
            ->remove('author');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $this->entityManager->persist($post);
            $this->entityManager->flush();


            $this->addFlash('success', 'Article mise à jour');
            return $this->redirectToRoute('post_read', ['post' => $post->getId()]);

        }


        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'last_posts_user' => $emPost->findLastPostsByUser($this->getUser()->getId()),
            'top_posts_user' => $emPost->findTopPostsByUser($this->getUser()->getId())
        ]);
    }


}
