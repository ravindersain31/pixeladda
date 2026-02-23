<?php

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Post;
use App\Form\Admin\Blog\PostType;
use App\Helper\ImageHelper;
use App\Helper\UploaderHelper;
use App\Repository\Blog\PostRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog')]
class PostController extends AbstractController
{
    #[Route('/posts', name: 'blog_posts')]
    public function index(Request $request, PaginatorInterface $paginator, PostRepository $repository): Response
    {
        $page = $request->get('page', 1);
        $posts = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/blog/posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/new', name: 'blog_post_new')]
    public function newPost(Request $request, SluggerInterface $slugger, PostRepository $repository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($post->getSlug() === null && empty($post->getSlug())) {
                $post->setSlug($slugger->slug($post->getTitle())->lower());
            }
            $repository->save($post, true);
            $this->addFlash('success', 'Post has been created successfully.');
            return $this->redirectToRoute('admin_blog_post_edit', ['id' => $post->getId()]);
        }
        return $this->render('admin/blog/posts/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/post/edit/{id}', name: 'blog_post_edit')]
    public function editPost(Post $post, Request $request, SluggerInterface $slugger, PostRepository $repository): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($post->getSlug() === null && empty($post->getSlug())) {
                $post->setSlug($slugger->slug($post->getTitle())->lower());
            }
            $post->setUpdatedAt(new \DateTimeImmutable());
            $repository->save($post, true);
            $this->addFlash('success', 'Post has been updated successfully.');
            return $this->redirectToRoute('admin_blog_post_edit', ['id' => $post->getId()]);
        }
        return $this->render('admin/blog/posts/form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }


    #[Route(path: '/post/upload', name: 'blog_upload_file', methods: ['POST'])]
    public function uploadArtwork(Request $request, UploaderHelper $uploader, ImageHelper $imageHelper): Response
    {
        $file = $request->files->get('file');

        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if (in_array($ext, ['ai', 'eps', 'pdf'])) {
            $converted = $imageHelper->toPng($file->getRealPath());
            if (!$converted['success']) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to upload your image [ERR_CONVERT]',
                ], 400);
            }
            $file = $uploader->createFileFromContents($converted['blob'], $fileName . '.png');
        }

        $uploader->setUploadPath('files');
        $url = $uploader->upload($file, 'blogFilesStorage');
        if ($url) {
            return $this->json([
                'success' => true,
                'location' => $url,
            ]);
        }
        return $this->json([
            'success' => false,
            'message' => 'Failed to upload your artwork [ERR_UPLOAD]',
        ], 400);
    }
}
