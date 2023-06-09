<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Utilisateur;
use App\Form\LieuType;
use App\Form\AnnulationSortieType;
use App\Form\SortieFilterType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use App\Repository\UtilisateurRepository;
use DateTime;
use phpDocumentor\Reflection\Types\This;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Date;

#[Route('/sortie', name: 'sortie_')]
class SortieController extends AbstractController
{
    #[Route('/add', name: 'add')]
    public function add(Request          $request,
                        SortieRepository $sortieRepository,
                        EtatRepository   $etatRepository,
                        LieuRepository   $lieuRepository): Response
    {
        $newSortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $newSortie);

        $newLieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $newLieu);

        $user = $this->getUser();

        $sortieForm->handleRequest($request);
        $lieuForm->handleRequest($request);


        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            dump($newLieu);
            $lieuRepository->save($newLieu, true);
        }


        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {

            $newSortie->setOrganisateur($user);
            $campus = $sortieForm->get('campus')->getData();
            $newSortie->setCampus($campus);
            $newSortie->setEtat($etatRepository->find('1')
            );


            $sortieRepository->save($newSortie, true);
            $this->addFlash('success', 'Sortie créée avec succès.');
            return $this->redirectToRoute('main_home');
        }

        return $this->render('sortie/add.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'lieuForm' => $lieuForm->createView()
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/list', name: 'list')]
    public function list(Request $request, SortieRepository $sortieRepository, EtatRepository $etatRepository): Response
    {

        $filterForm = $this->createForm(SortieFilterType::class);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $sorties = $sortieRepository->findByFilters($filterForm);

            return $this->render('sortie/list.html.twig',[
                'sorties' => $sorties,
                'filterForm' => $filterForm->createView()
            ]);
        }

        $sorties = $sortieRepository->findAll();
        $dateDuJour = new DateTime();


        foreach ($sorties as $sortie) {
            $minutesAAjouter = $sortie->getDuree();
            $finSortie = clone $sortie->getDateHeureDebut();
            $finSortie->modify("+{$minutesAAjouter} minutes");

            $dateCloture = $sortie->getDateLimiteInscription();
            if ($sortie->getEtat() !== $etatRepository->find(6)) {
                if ($dateDuJour > $dateCloture && $dateDuJour < $sortie->getDateHeureDebut()) {
                    $sortie->setEtat($etatRepository->find(3));
                } elseif ($dateDuJour <= $finSortie && $dateDuJour >= $sortie->getDateHeureDebut()) {
                    $sortie->setEtat($etatRepository->find(4));
                } elseif ($dateDuJour > $finSortie) {
                    $sortie->setEtat($etatRepository->find(5));
                }
                $sortieRepository->save($sortie, true);
            }
        }

        $sortie = $sortieRepository->findBy([], ["nom" => "ASC"]);


        return $this->render('sortie/list.html.twig', [
            'sorties' => $sortie,
            'filterForm' => $filterForm->createView()
        ]);

    }

    #[Route('/detail/{id}', name: 'show', requirements: ["id" => "\d+"])]
    public function show(int $id, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException("Pas de sortie trouvée !");
        }
        return $this->render('sortie/show.html.twig', ['sortie' => $sortie]);
    }


    #[Route('/update/{id}', name: 'update', requirements: ["id" => "\d+"])]
    public function edit(Request $request, int $id, SortieRepository $sortieRepository): Response
    {

        $sortie = $sortieRepository->find($id);
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted()) {
            $sortieRepository->save($sortie, true);
            $this->addFlash('success', 'Sortie modifiée avec succès.');
            return $this->redirectToRoute('main_home');
        };

        return $this->render('sortie/update.html.twig', [
            'sortieForm' => $sortieForm->createView()
        ]);

    }

    #[Route('/delete/{id}', name: 'delete', requirements: ["id" => "\d+"])]
    public function delete(Request $request, int $id, SortieRepository $sortieRepository): Response
    {

        $sortie = $sortieRepository->find($id);

        if ($sortie->getEtat()->getLibelle() != 'Activité en cours' || $sortie->getEtat()->getLibelle() != 'Passée' || $sortie->getEtat()->getLibelle() != 'Clôturée'
            || $sortie->getEtat()->getLibelle() != 'Annulée') {
            $sortie = $sortieRepository->find($id);
            $sortieRepository->remove($sortie, true);

            $this->addFlash('success', 'Sortie supprimée avec succès.');
            return $this->redirectToRoute('main_home');
        }

        $this->addFlash('error', 'Vous ne pouvez pas supprimer cette sortie.');
        return $this->redirectToRoute('main_home');

    }

    #[Route('/publish/{id}', name: 'publish', requirements: ["id" => "\d+"])]
    public function publish(Request $request, int $id, SortieRepository $sortieRepository, EtatRepository $etatRepository): Response
    {
        $sortie = $sortieRepository->find($id);

        if ($sortie->getEtat()->getId() == '1') {
            $sortie->setEtat($etatRepository->find('2'));
            $sortieRepository->save($sortie, true);

            $this->addFlash('success', 'Sortie publiée.');
            return $this->redirectToRoute('main_home');
        }

        $this->addFlash('error', 'La sortie est déjà ' . $sortie->getEtat()->getLibelle() . '.');
        return $this->redirectToRoute('sortie_list');

    }

    #[Route('/subscribe/{id}', name: 'subscribe', requirements: ["id" => "\d+"])]
    public function subscribe(int $id, SortieRepository $sortieRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $utilisateurRepository->findOneBy(['username' => $this->getUser()->getUserIdentifier()]);
        $dateDuJour = new DateTime();

        $sortie = $sortieRepository->find($id);

        //Ya peut-être moyen que ça fonctionne
        //On vérifie que l'utilisateur qui veut s'inscrire n'est pas déjà inscrit
        // S'il valide la condition on l'inscrit dans la sortie
        if (!$sortie->getUtilisateurs()->contains($this->getUser()) &&
            $sortie->getDateLimiteInscription() > $dateDuJour &&
            $sortie->getNbMaxInscriptions() > $sortie->getUtilisateurs()->count()) {


            $user->addSortie($sortie);
            $utilisateurRepository->save($user, true);
            $this->addFlash('success', 'Inscription validée.');
            return $this->redirectToRoute('sortie_list', ['id' => $sortie->getId()]);

        }

        return $this->redirectToRoute('sortie_list');

    }

    #[Route('/unsubscribe/{id}', name: 'unsubscribe', requirements: ["id" => "\d+"])]
    public function unsubscribe(int $id, SortieRepository $sortieRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $utilisateurRepository->findOneBy(['username' => $this->getUser()->getUserIdentifier()]);
        $sortie = $sortieRepository->find($id);

        $dateDuJour = new DateTime();

        if ($sortie->getUtilisateurs()->contains($this->getUser()) && $sortie->getDateLimiteInscription() > $dateDuJour) {
            $user->removeSortie($sortie);
            $utilisateurRepository->save($user, true);
            $this->addFlash('success', 'Vous avez été désinscrit de cette sortie.');
            return $this->redirectToRoute('sortie_list', ['id' => $sortie->getId()]);
        }

        $this->addFlash('error', 'Vous ne pouvez pas vous désinscire.');
        return $this->redirectToRoute('sortie_list');

    }


    #[Route('/cancel/{id}', name: 'cancel', requirements: ["id" => "\d+"])]
    public function cancel(Request $request, int $id, SortieRepository $sortieRepository, EtatRepository $etatRepository): Response
    {
        $sortie = $sortieRepository->find($id);

        $annulerSortieForm = $this->createForm(AnnulationSortieType::class, $sortie);

        $annulerSortieForm->handleRequest($request);

        if ($annulerSortieForm->isSubmitted() && $annulerSortieForm->isValid()) {
            $sortie->setEtat($etatRepository->find('6'));
            $sortieRepository->save($sortie, true);
            $this->addFlash('success', 'Sortie annulée avec succès.');
            return $this->redirectToRoute('main_home');
        }

        return $this->render('sortie/cancel.html.twig', [
            'annulerSortieForm' => $annulerSortieForm->createView()
        ]);

    }
}

