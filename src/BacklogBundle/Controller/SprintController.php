<?php


namespace BacklogBundle\Controller;


use PHPRum\Commands\Backlog\CreateSrpint;
use PHPRum\DomainModel\Backlog\Sprint;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class SprintController extends Controller
{

    /**
     * @Route("/sprint/", name="sprint_list")
     * @Method({"GET"})
     */
    public function listSprintsAction()
    {
        $repository = $this->getDoctrine()->getManager()->getRepository(Sprint::class);
        return $this->render(
            'backlog/sprint_list.html.twig',
            [
                'items' => $repository->findByCreator($this->getUser())
            ]
            );
    }

    /**
     * @Route("/sprint/{id}", name="show_sprint")
     * @Method({"GET"})
     */
    public function showSprintAction($id)
    {
        $repository = $this->getDoctrine()->getManager()->getRepository(Sprint::class);
        /** @var Sprint $sprint */
        $sprint = $repository->find($id);

        return $this->render(
            'backlog/sprint_item_list.html.twig',
            [
                'items' => $sprint->getItems(),
                'points_sum' => $sprint->getTotalPoints()
            ]
        );
    }

    /**
     * @Route("/sprint/new", name="create_sprint")
     * @Method({"POST", "GET"})
     */
    public function addSprintAction(Request $request)
    {
        $createSprintCommand = new CreateSrpint($this->getDoctrine()->getManager());

        $form = $this->createFormBuilder($createSprintCommand)
            ->add('duration', ChoiceType::class, [
                'choices' => [
                    'One week' => '1_week',
                    'Two weeks' => '2_week',
                    'Three weeks' => '3_week',
                    'Four weeks' => '4_week'
                ]
            ])
            ->add('Save', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateSrpint $command */
            $command = $form->getData();
            $command->setUser($this->getUser());
            $command->execute();

            $this->addFlash('notice', 'New sprint added');
            return $this->redirectToRoute('list_backlog_items');
        }

        return $this->render('backlog/add_sprint.html.twig', [
            'form' => $form->createView()
        ]);
    }
}