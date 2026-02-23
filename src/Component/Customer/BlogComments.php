<?php

namespace App\Component\Customer;

use App\Entity\AppUser;
use App\Entity\Blog\Comment;
use App\Entity\Blog\Post;
use App\Entity\User;
use App\Form\BlogCommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "BlogComments",
    template: "blog/_post_comments.html.twig"
)]
class BlogComments extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public Post $post;

    public array $comments;


    public function __construct(
        private readonly MailerInterface        $mailer,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    protected function instantiateForm(): FormInterface
    {
        $this->comments = $this->entityManager->getRepository(Comment::class)->listByPost($this->post)->getResult();
        return $this->createForm(BlogCommentType::class);
    }

    #[LiveAction]
    public function submitComment(Request $request): void
    {
        $this->submitForm();
        $form = $this->getForm();

        if (!$form->isValid()) {
            return;
        }

        try {
            $data = $form->getData();

            $user = $this->entityManager->getRepository(AppUser::class)->findOneBy(['email' => $data['email']]);

            $comment = new Comment();
            $comment->setPost($this->post);
            $comment->setUser($user);
            $comment->setName($data['name']);
            $comment->setEmail($data['email']);
            $comment->setContent($data['comment']);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your comment has been submitted successfully. It will be visible after moderation.');

            $this->resetForm();

        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Something went wrong. Please try again later.');
        }
    }

}