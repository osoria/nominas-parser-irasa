<?php

namespace App\Controller;

use App\Form\NominaPdfType;
use App\Service\PdfParserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class NominaController extends AbstractController
{
    /**
     * @var PdfParserInterface
     */
    private $pdfParser;

    public function __construct(PdfParserInterface $pdfParser)
    {
        $this->pdfParser = $pdfParser;
    }

    /**
     * @Route("/", name="new")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(NominaPdfType::class);
        $form->handleRequest($request);

        $log = null;

        if ($form->isSubmitted() && $form->isValid()) {

            list($path, $file) = $this->saveNominaFile($form);

            if (!$path || !$file) {
                throw new HttpException(500, 'El fichero PDF no ha sido grabado');
            }

            $log = $this->pdfParser->execute($path, $file, $form['test']->getData());
        }

        return $this->render('nomina/index.html.twig', [
            'form' => $form->createView(),
            'log' => $log,
            'test_mail' => $_SERVER['EMAIL_TEST']
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormInterface $form
     * @return string
     * @throws FileException
     */
    public function saveNominaFile(\Symfony\Component\Form\FormInterface $form): ?array
    {
        /** @var UploadedFile $nominasFile */
        $nominasFile = $form['pdf']->getData();

        if ($nominasFile) {
            $originalFilename = pathinfo($nominasFile->getClientOriginalName(), PATHINFO_FILENAME);

            $folder = "../data/nominas/$originalFilename";
            if (!file_exists($folder)) {
                mkdir($folder);
            }

            $safeFilename = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                $originalFilename
            );
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $nominasFile->guessExtension();


            $nominasFile->move($folder, $newFilename);

            return [$folder, $newFilename];
        }

        return null;
    }
}
