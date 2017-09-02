<?php


namespace BacklogBundle\Controller;

use BacklogBundle\BacklogBundle;
use BacklogBundle\Entity\Item;
use BacklogBundle\Entity\User;
use BacklogBundle\Form\CreateItemType;
use BacklogBundle\Form\CreateSubItemType;
use BacklogBundle\Form\UpdateItemType;
use BacklogBundle\Repository\ItemRepository;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\{
    SubmitType, TextType
};
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BacklogController extends Controller
{
    /**
     * @Route("/backlog/", name="list_backlog_items")
     * @Method("GET")
     */
    public function listItemsAction(Request $request)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        $items = $repository->getByPage($this->getUser()->getId(), $request->get('page', 1),
            BacklogBundle::MAX_ITEMS_PER_PAGE);

        return $this->render('backlog/item_list.html.twig', ['items' => $items]);
    }

    /**
     * @Route("/backlog/new", name="add_backlog_item")
     * @Method({"POST", "GET"})
     */
    public function addItemAction(Request $request)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(CreateItemType::class, null, [
            'user' => $user,
            'backlog' => $repository->getFullBacklog($user->getId())
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * add item to backlog and save it
             */
            /** @var Item $item */
            $item = $form->getData();
            $this->getDoctrine()->getManager()->persist($item);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('list_backlog_items');
        }

        return $this->render('backlog/item_add.html.twig', ['form' => $form->createView(), 'path' => null]);
    }


    /**
     * @Route("/backlog/{id}/edit", name="edit_item")
     * @Method({"POST", "GET"})
     */
    public function editItemAction($id, Request $request)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        $item = $repository->findOneById($id);

        $sprintQuery = function (EntityRepository $er) {
            $queryBuilder = $er->createQueryBuilder('Sprints');
            return $queryBuilder
                ->select()
                ->where($queryBuilder->expr()->eq('Sprints.creator', '?1'))
                ->setParameter(1, $this->getUser()->getId());
        };
        \Closure::bind($sprintQuery, $this);

        $form = $this->createForm(UpdateItemType::class, $item, ['sprint_query' => $sprintQuery]);

        $form->handleRequest($request);

        $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');
        $path = $helper->asset($item, 'imageFile');

        if ($form->isSubmitted() && $form->isValid()) {

            $item = $form->getData();
            $this->getDoctrine()->getManager()->persist($item);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('list_backlog_items');
        }

        return $this->render('backlog/item_edit.html.twig',
            ['form' => $form->createView(), 'path' => $path, 'item' => $item]);
    }

    /**
     * @Route("/backlog/{id}/add-sub-task", name="add_sub_task")
     * @Method({"POST", "GET"})
     */
    public function addSubTask($id, Request $request)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        $parentItem = $repository->findOneById($id);

        $form = $this->createForm(CreateSubItemType::class, null, ['parent_item' => $parentItem]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subItem = $form->getData();
            $objectManager = $this->getDoctrine()->getManager();
            $objectManager->persist($subItem);
            $objectManager->flush();

            return $this->redirectToRoute('edit_item', ['id' => $id]);
        }

        return $this->render('backlog/sub_item_add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/backlog/{id}/delete", name="delete_item")
     * @Method({"GET"})
     */
    public function deleteItemAction($id)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        $item = $repository->findOneById($id);

        $this->getDoctrine()->getManager()->remove($item);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('list_backlog_items');
    }

    /**
     * @Route("/backlog/{id}/priority", name="change_priority")
     * @Method({"POST"})
     */
    public function changeItemPriority($id, Request $request)
    {
        /** @var ItemRepository $repository */
        $repository = $this->get('item_repository');

        $user = $this->getUser();
        $backlog = $repository->getFullBacklog($user->getId());

        $priority = (int)$request->request->get('priority');

        $backlog->changeItemPriority($id, $priority);

        $objectManager = $this->getDoctrine()->getManager();
        foreach ($backlog->getItems() as $item) {
            $objectManager->persist($item);
        }
        $objectManager->flush();

        return new Response('', 200);
    }
}