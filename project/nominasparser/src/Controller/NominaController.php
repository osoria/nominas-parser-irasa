<?php

namespace App\Controller;

use App\Form\NominaPdfType;
use App\Repository\EmpleadoRepository;
use App\Service\PdfParserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class NominaController extends AbstractController
{
    /**
     * @var PdfParserInterface
     */
    private $pdfParser;

    /**
     * @var KernelInterface
     */
    private $appKernel;

    public function __construct(PdfParserInterface $pdfParser, KernelInterface $appKernel)
    {
        $this->pdfParser = $pdfParser;
        $this->appKernel = $appKernel;
    }

    /**
     * @Route("/", name="new")
     */
    public function index(Request $request, EmpleadoRepository $empleadoRepository)
    {
        $form = $this->createForm(NominaPdfType::class);
        $form->handleRequest($request);

        $log = null;

        if ($form->isSubmitted() && $form->isValid()) {

            list($path, $file) = $this->saveNominaFile($form);

            if (!$path || !$file) {
                throw new HttpException(500, 'El fichero PDF no ha sido grabado');
            }

            $empleadosSelected = $request->get('empleado');

            $log = $this->pdfParser->execute($path, $file, $form['mode']->getData(), $empleadosSelected);
        }

        $empleados = $empleadoRepository->findAll();

        return $this->render('nomina/index.html.twig', [
            'form' => $form->createView(),
            'log' => $log,
            'empleados' => $empleados
        ]);
    }

    /**
     * @param FormInterface $form
     * @return string
     * @throws FileException
     */
    private function saveNominaFile(FormInterface $form): ?array
    {
        /** @var UploadedFile $nominasFile */
        $nominasFile = $form['pdf']->getData();

        if ($nominasFile) {
            $originalFilename = pathinfo($nominasFile->getClientOriginalName(), PATHINFO_FILENAME);

            $folder = "{$this->appKernel->getProjectDir()}/data/nominas/$originalFilename";
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
