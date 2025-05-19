<?php

namespace App\Controller;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\LivreRepository;

use App\Entity\Commentaire;
use App\Form\CommentaireTypeForm;
use App\Form\LivreTypeForm;
use App\Entity\Livre;

use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;



class LivreController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AiService $aiService;

    public function __construct(EntityManagerInterface $entityManager, AiService $aiService)
    {
        $this->entityManager = $entityManager;
        $this->aiService = $aiService;
    }

    #[Route('/livres', name: 'livre_index')]
    public function index(LivreRepository $livreRepository): Response
    {$user = $this->getUser();
        $livres = $livreRepository->findAll();
        return $this->render('index.html.twig', [
            'livres' => $livres,
            'user' => $user,
        ]);
    }

    #[Route('/livre/nouveau', name: 'livre_new')]
    public function new(Request $request): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreTypeForm::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($livre);
            $this->entityManager->flush();

            return $this->redirectToRoute('livre_index');
        }

        return $this->render('new.html.twig', [
            'livre' => $livre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/livre/{id}/modifier', name: 'livre_edit')]
    public function edit(Request $request, Livre $livre): Response
    {
        $form = $this->createForm(LivreTypeForm::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('livre_index');
        }

        return $this->render('edit.html.twig', [
            'livre' => $livre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/livre/{id}/supprimer', name: 'livre_delete')]
    public function delete(Livre $livre): Response
    {
        $this->entityManager->remove($livre);
        $this->entityManager->flush();

        return $this->redirectToRoute('livre_index');
    }

    

    #[Route('/livre/{id}/analyse', name: 'livre_analyse')]
    public function analyse(int $id, LivreRepository $livreRepository, HttpClientInterface $httpClient): Response
    {
        $livre = $livreRepository->find($id);
    
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }
    
        sleep(1); // Facultatif
    
        // Analyse avec Gemini
        try {
            $apiKey = $_ENV['GEMINI_API_KEY'];
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    
            $prompt = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => "Peux-tu analyser le livre suivant : " . $livre->getTitre()]
                        ]
                    ]
                ]
            ];
    
            $response = $httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $prompt,
            ]);
    
            $data = $response->toArray();
            $analyse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Analyse non disponible';
        } catch (\Exception $e) {
            $analyse = 'Erreur API Gemini : ' . $e->getMessage();
        }
    
        // 🔍 Recherche d'une image du livre via Google Books
        $image = null;
        try {
            $googleBooksUrl = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($livre->getTitre() . ' inauthor:' . $livre->getAuteur());
    
            $bookResponse = $httpClient->request('GET', $googleBooksUrl);
            $bookData = $bookResponse->toArray();
    
            if (isset($bookData['items'][0]['volumeInfo']['imageLinks']['thumbnail'])) {
                $image = $bookData['items'][0]['volumeInfo']['imageLinks']['thumbnail'];
            }
        } catch (\Exception $e) {
            $image = null;
        }
    
        return $this->render('analyse.html.twig', [
            'livre' => $livre,
            'analyse' => $analyse,
            'image' => $image,
        ]);
    }
    






#[Route('/about', name: 'about')]
public function about(): Response
{
    return $this->render('about.html.twig');
}

// Route pour la page "Contact"
#[Route('/contact', name: 'contact')]
public function contact(): Response
{
    return $this->render('contact.html.twig');
}
    

#[Route('/livre/{id}/resumer', name: 'livre_summary')]
public function livreSummary(int $id, LivreRepository $livreRepository, HttpClientInterface $httpClient): Response
{
    $livre = $livreRepository->find($id);

    if (!$livre) {
        throw $this->createNotFoundException('Livre non trouvé');
    }

    sleep(1); // Facultatif pour simuler le chargement

    // --- 1. Récupération du résumé depuis Gemini ---
    try {
        $apiKey = $_ENV['GEMINI_API_KEY'];
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

        $prompt = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => "Peux-tu me faire un résumé du livre suivant : " . $livre->getTitre()]
                    ]
                ]
            ]
        ];

        $response = $httpClient->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $prompt,
        ]);

        $data = $response->toArray();
        $summary = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Résumé non disponible';
    } catch (\Exception $e) {
        $summary = 'Erreur de communication avec l\'API Gemini : ' . $e->getMessage();
    }

    // --- 2. Récupération de l'image via Google Books API ---
    $image = null;
    try {
        $googleBooksUrl = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($livre->getTitre() . ' inauthor:' . $livre->getAuteur());
        $bookResponse = $httpClient->request('GET', $googleBooksUrl);
        $bookData = $bookResponse->toArray();

        if (isset($bookData['items']) && count($bookData['items']) > 0) {
            foreach ($bookData['items'] as $item) {
                if (isset($item['volumeInfo']['imageLinks']['thumbnail'])) {
                    $image = $item['volumeInfo']['imageLinks']['thumbnail'];
                    break;
                }
            }
        }

        if (!$image) {
            $image = 'https://via.placeholder.com/128x180?text=Image+non+disponible';
        }
    } catch (\Exception $e) {
        $image = 'https://via.placeholder.com/128x180?text=Erreur+image';
    }

    return $this->render('summary.html.twig', [
        'livre' => $livre,
        'summary' => $summary,
        'image' => $image,
    ]);
}



#[Route('/livre/{id}/commenter', name: 'livre_comment')]
public function livreComment(int $id, Request $request, LivreRepository $livreRepository, EntityManagerInterface $entityManager): Response
{
    // Trouver le livre en fonction de l'ID
    $livre = $livreRepository->find($id);

    // Si le livre n'est pas trouvé, afficher une erreur
    if (!$livre) {
        throw $this->createNotFoundException('Livre non trouvé');
    }

    // Créer un nouvel objet Commentaire
    $commentaire = new Commentaire();

    // Créer le formulaire pour le commentaire
    $form = $this->createForm(CommentaireTypeForm::class, $commentaire);
    $form->handleRequest($request);

    // Si le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        // Assigner l'utilisateur actuel au commentaire
        $commentaire->setUser($this->getUser());
        // Assigner le livre au commentaire
        $commentaire->setLivre($livre);

        // Persister et enregistrer le commentaire
        $entityManager->persist($commentaire);
        $entityManager->flush();

        // Rediriger vers la page du livre
        return $this->redirectToRoute('livre_show', ['id' => $livre->getId()]);
    }

    // Passer la variable 'livre' et 'form' au template
    return $this->render('comment.html.twig', [
        'form' => $form->createView(),
        'livre' => $livre, // Ajouter la variable livre
    ]);
}


#[Route('/mes-livres', name: 'user_livre_list')]
public function mesLivres(LivreRepository $livreRepository): Response
{
    // Récupère tous les livres sans filtrer
    $livres = $livreRepository->findAll();

    // Rend la vue en passant la liste des livres
    return $this->render('listelivrescoteuser.html.twig', [
        'livres' => $livres,
    ]);
}

#[Route('/livre/{id}', name: 'livre_show')]
    public function show(Livre $livre): Response
    {
        return $this->render('show.html.twig', [
            'livre' => $livre,
        ]);
    }




}
