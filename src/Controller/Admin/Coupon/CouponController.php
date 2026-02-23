<?php

namespace App\Controller\Admin\Coupon;

use App\Entity\Admin\Coupon;
use App\Form\Admin\Coupon\CouponType;
use App\Repository\Admin\CouponRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/coupon')]
class CouponController extends AbstractController
{
    #[Route('/', name: 'coupon')]
    public function index(Request $request, CouponRepository $couponRepository, PaginatorInterface $paginator): Response
    {
        $couponAll = $couponRepository->findAllRegularCoupons();
        $page = $request->get('page', 1);
        $coupon = $paginator->paginate($couponAll, $page, 32);
        return $this->render('admin/coupon/index.html.twig', [
            'coupon' => $coupon,
        ]);
    }

    #[Route('/edit/{id}', name: 'coupon_edit')]
    public function editCoupon(Coupon $coupon, Request $request, CouponRepository $couponRepository): Response
    {
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $couponRepository->save($coupon, true);
            $this->addFlash('success', 'Coupon has been updated successfully.');
            return $this->redirectToRoute('admin_coupon');
        }
        
        return $this->render('admin/coupon/edit.html.twig', [
            'form' => $form,
            'coupon' => $coupon
        ]);
    }

    #[Route('/add', name:'coupon_add')]
    public function addCoupon(Request $request, CouponRepository $couponRepository): Response
    {
        $coupon = new Coupon;
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $couponRepository->save($coupon, true);
            $this->addFlash('success', 'Coupon has been created successfully.');
            return $this->redirectToRoute('admin_coupon');
        }

        return $this->render('admin/coupon/add.html.twig', [
            'form' => $form,
            'coupon' => $coupon
        ]);
    }
    
    #[Route('/change-status/{id}/{status}', name: 'coupon_status')]
    public function changeStatus(Request $request, Coupon $coupon, CouponRepository $couponRepository): Response
    {
        $status = $request->get('status') === '0' ? true : false;
        $coupon->setIsEnabled($status);
        $couponRepository->save($coupon, true);
        $action = $status ? 'Enabled' : 'Disabled';
        $this->addFlash('success', "$action successfully");

        return $this->redirectToRoute('admin_coupon');
    }
}
