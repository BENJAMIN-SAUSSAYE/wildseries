<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Program;
use App\Entity\Season;
use App\Form\ProgramType;
use App\Repository\ProgramRepository;
use App\Repository\UserRepository;
use App\Service\ProgramDuration;
use App\Service\UserAdressService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/program', name: 'app_program_')]
class ProgramController extends AbstractController
{
    #[Route('/', methods: ['GET'], name: 'index')]
    public function index(ProgramRepository $programRepository): Response
    {
        return $this->render('program/index.html.twig', ['programs' => $programRepository->findAll()]);
    }

    #[IsGranted('ROLE_CONTRIBUTOR')]
    #[Route('/new', name: 'new')]
    public function new(Request $request, MailerInterface $mailer, ProgramRepository $programRepository, SluggerInterface $slugger, UserAdressService $userAdressService): Response
    {
        // Create a new Category Object
        $program = new Program();
        $program->setOwner($this->getUser());
        // Create the form, linked with $category
        $form = $this->createForm(ProgramType::class, $program);

        // Get data from HTTP request
        $form->handleRequest($request);

        // Was the form submitted ?
        if ($form->isSubmitted() && $form->isValid()) {
            $program->setSlug($slugger->slug($program->getTitle()));
            // Deal with the submitted data
            // For example : persiste & flush the entity
            // And redirect to a route that display the result
            $programRepository->save($program, true);

            $this->addFlash(
                'success',
                'La série a été ajoutée'
            );

            //get list of adress of User with role Contributor
            $recipients = $userAdressService->getUserAdressFromSpecificRole('ROLE_CONTRIBUTOR');

            $email = (new Email())
                ->from($this->getParameter('mailer_from'))
                ->to(...$recipients)
                ->subject('Une nouvelle série vient d\'être publiée !')
                ->html($this->renderView(
                    'program/newProgramEmail.html.twig',
                    ['program' => $program]
                ));

            $mailer->send($email);

            // Redirect to categories list
            return $this->redirectToRoute('app_program_index');
        }

        // Render the form
        return $this->render('program/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', methods: ['GET'], name: 'show')]
    public function show(Program $program, ProgramDuration $programDuration): Response
    {
        return $this->render('program/show.html.twig', [
            'program' => $program,
            'programDuration' => $programDuration->calculate($program),
        ]);
    }

    #[Route('/{slug}/season/{season}', requirements: ['season' => '\d+'], methods: ['GET'], name: 'season_show')]
    public function showSeason(Program $program, Season $season): Response
    {
        return $this->render('program/season_show.html.twig', ['program' => $program, 'season' => $season]);
    }

    #[Route('/{program}/season/{season}/episode/{episode}', requirements: ['season' => '\d+'], methods: ['GET'], name: 'episode_show')]
    #[ParamConverter('program', options: ['mapping' => ['program' => 'slug']])]
    #[ParamConverter('episode', options: ['mapping' => ['episode' => 'slug']])]

    public function showEpisode(Program $program, Season $season, Episode $episode): Response
    {
        return $this->render('program/episode_show.html.twig', ['program' => $program, 'season' => $season, 'episode' => $episode]);
    }

    #[IsGranted('ROLE_CONTRIBUTOR')]
    #[Route('/{slug}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Program $program, programRepository $programRepository, SluggerInterface $slugger): Response
    {
        // Check wether the logged in user is the owner of the program
        if ($this->getUser() !== $program->getOwner()) {
            // If not the owner, throws a 403 Access Denied exception
            throw $this->createAccessDeniedException('Seul l\'auteur de cette série peut la modifier !');
        }

        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($program->getTitle());
            $program->setSlug($slug);
            $programRepository->save($program, true);
            $this->addFlash(
                'success',
                'La série a été modifiée'
            );

            // Redirect to programs list
            return $this->redirectToRoute('app_program_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('program/edit.html.twig', [
            'program' => $program,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_CONTRIBUTOR')]
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Program $program, ProgramRepository $programRepository): Response
    {
        // Check wether the logged in user is the owner of the program
        if ($this->getUser() !== $program->getOwner()) {
            // If not the owner, throws a 403 Access Denied exception
            throw $this->createAccessDeniedException('Seul l\'auteur de cette série peut la supprimer !');
        }

        if ($this->isCsrfTokenValid('delete' . $program->getId(), $request->request->get('_token'))) {
            $programRepository->remove($program, true);
            $this->addFlash(
                'danger',
                'La série a été supprimée'
            );
        }

        return $this->redirectToRoute('app_program_index', [], Response::HTTP_SEE_OTHER);
    }
}
