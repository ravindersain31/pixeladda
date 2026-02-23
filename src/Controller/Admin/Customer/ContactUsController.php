<?php

namespace App\Controller\Admin\Customer;

use App\Entity\ContactUs;
use App\Repository\ContactUsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('contact-us')]
class ContactUsController extends AbstractController
{
    #[Route('/', name: 'contact_us')]
    public function index(Request $request, ContactUsRepository $contactUsRepository, PaginatorInterface $paginator): Response
    {
        $enquiries = $contactUsRepository->findBy([], ['createdAt' => 'DESC']);
        $page = $request->get('page', 1);
        $enquiries = $paginator->paginate($enquiries, $page, 32);
        return $this->render('admin/customer/contact_enquiry/index.html.twig', [
            'enquiries' => $enquiries,
        ]);
    }

    #[Route('/view/{id}', name: 'contact_us_view')]
    public function view(Request $request, ContactUs $contactUs): Response
    {
        return $this->render('admin/customer/contact_enquiry/view.html.twig',[
            'contactUs' => $contactUs
        ]);
    }

    #[Route('/remove/{id}', name: 'contact_enquiry_remove')]
    public function remove(ContactUs $contactUs, ContactUsRepository $contactUsRepository): Response
    {
        $contactUsRepository->remove($contactUs, true);
        $this->addFlash('success', 'Enquiry removed successfully');
        return $this->redirectToRoute('admin_contact_enquiry');
    }

    #[Route('/status/{id}/{status}', name: 'contact_us_status')]
    public function changeStatus(Request $request, ContactUs $contactUs, ContactUsRepository $contactUsRepository): Response
    {
        $isOpened = $request->get('status') === '1';
        $contactUs->setIsOpened($isOpened);
        $contactUs->setUpdatedAt($isOpened ? null : new \DateTimeImmutable());
        $contactUsRepository->save($contactUs, true);
        $message = 'Contact Request ' . ($isOpened ? 'Open' : 'Completed') . ' successfully';
        $this->addFlash('success', $message);
        return $this->redirectToRoute('admin_contact_us');
    }
}
