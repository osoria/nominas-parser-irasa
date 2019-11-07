<?php

namespace App\Service;

use App\Repository\EmpleadoRepository;
use Smalot\PdfParser\Parser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PdfParser implements PdfParserInterface
{
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var EmpleadoRepository
     */
    private $empleadoRepository;
    /**
     * @var PdfCutInterface
     */
    private $pdfCut;
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(
        Parser $parser,
        EmpleadoRepository $empleadoRepository,
        PdfCutInterface $pdfCut,
        MailerInterface $mailer
    ) {
        $this->parser = $parser;
        $this->empleadoRepository = $empleadoRepository;
        $this->pdfCut = $pdfCut;
        $this->mailer = $mailer;
    }

    public function execute(string $path, string $file, bool $test = false): string
    {
        $pdf = $this->parser->parseFile("$path/$file");
        $this->pdfCut->init("$path/$file");

        $pages = $pdf->getPages();

        $log = '';
        $numPage = 0;
        foreach ($pages as $page) {
            $numPage++;
            $text = $page->getText();
            $parsed = explode(',', $text);
            $apellidos = trim($parsed[0]);
            preg_match('/^(.*)$/m', $parsed[1], $matches);
            $nombre = trim($matches[0]);
            $empleado = $this->empleadoRepository->findOneBy(['nombre' => $nombre, 'apellidos' => $apellidos]);

            $nombre = ucwords(strtolower($nombre));
            $apellidos = ucwords(strtolower($apellidos));
            if (!$empleado) {
                $log .= "<strong>ATENCIÓN:</strong> El empleado $nombre $apellidos no se ha encontrado en la BD y no " .
                        "se ha podido enviar su nómina<br/>";
                continue;
            }

            $pdf = $this->pdfCut->cut($numPage);
            $newFile = "$path/$nombre $apellidos.pdf";
            $result = $pdf->saveAs($newFile);

            if (!$result) {
                $log .= "<strong>ATENCIÓN:</strong> No se ha podido guardar la nómina $newFile<br/>";
            }

            $emailToSend = $empleado->getEmail();
            if ($test) {
                $emailToSend = $_SERVER['EMAIL_TEST'];
            }
            if (!$emailToSend) {
                $log .= "<strong>ATENCIÓN:</strong> El empleado $nombre $apellidos no tiene un email asociado<br/>";
                continue;
            }

            $email = (new Email())
                ->from($_SERVER['MAILER_SEND_BY'])
                ->to($emailToSend)
                ->subject('Su nómina del último mes')
                ->attachFromPath($newFile)
                ->text("Hola {$nombre}, le adjuntamos su última nómina")
                ->html("Hola $nombre,<br/><br/>Le adjuntamos su última nómina.<br/><br/>Saludos,");

            $this->mailer->send($email);

            $log .= "Se ha enviado correctamente la nómina a $nombre $apellidos<br/>";

            if ($test) {
                return $log;
            }
        }
        return $log;
    }
}
