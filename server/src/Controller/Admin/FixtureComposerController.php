<?php

namespace App\Controller\Admin;

use App\Form\Admin\Model\FixtureFullInput;
use App\Form\Admin\Model\OfficialInput;
use App\Form\Admin\Model\StaffInput;
use App\Form\Admin\Type\FixtureFullType;
use App\Service\Admin\FixtureFullCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FixtureComposerController extends AbstractController
{
    #[Route('/admin/fixture/new-complete', name: 'admin_fixture_new_complete')]
    public function newComplete(Request $request, FixtureFullCreator $creator): Response
    {
        $data = new FixtureFullInput();

        $refRoles = ['REFEREE_MAIN', 'REFEREE_ASSISTANT_1', 'REFEREE_ASSISTANT_2', 'REFEREE_FOURTH'];
        foreach ($refRoles as $role) {
            $official = new OfficialInput();
            $official->role = $role;
            $data->officials[] = $official;
        }

        for ($i = 0; $i < 6; ++$i) {
            $data->lineups[] = new \App\Form\Admin\Model\LineupInput();
        }

        for ($i = 0; $i < 2; ++$i) {
            $data->goals[] = new \App\Form\Admin\Model\GoalInput();
            $data->substitutions[] = new \App\Form\Admin\Model\SubstitutionInput();
        }

        foreach (['HEAD_COACH', 'ASSISTANT_COACH', 'ASSISTANT_COACH'] as $role) {
            $staff = new StaffInput();
            $staff->role = $role;
            $data->staff[] = $staff;
        }

        $form = $this->createForm(FixtureFullType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fixture = $creator->create($data);
            $this->addFlash('success', sprintf('Match #%d créé avec succès.', $fixture->getId()));

            return $this->redirectToRoute('admin_fixture_new_complete');
        }

        return $this->render('admin/fixture/new_complete.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
