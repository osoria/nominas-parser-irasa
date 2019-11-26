<?php

namespace App\Service;

use App\Repository\EmpleadoRepository;
use Smalot\PdfParser\Parser;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Mailer;
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

    public function __construct(
        Parser $parser,
        EmpleadoRepository $empleadoRepository,
        PdfCutInterface $pdfCut
    ) {
        $this->parser = $parser;
        $this->empleadoRepository = $empleadoRepository;
        $this->pdfCut = $pdfCut;
    }

    public function execute(string $path, string $file, bool $test = false): string
    {
        $pdf = $this->parser->parseFile("$path/$file");

        $pages = $pdf->getPages();

        $log = '';
        $numPage = 0;
        foreach ($pages as $page) {
            $numPage++;
            $text = $page->getText();
            $parsed = explode(',', $text);
            $apellidos = trim(preg_replace('/\t+/', '', $parsed[0]));
            preg_match('/^(.*)$/m', $parsed[1], $matches);
            $nombre = trim(preg_replace('/\t+/', '', $matches[0]));
            $empleado = $this->empleadoRepository->findOneBy(['nombre' => $nombre, 'apellidos' => $apellidos]);

            $nombre = ucwords(strtolower($nombre));
            $apellidos = ucwords(strtolower($apellidos));
            if (!$empleado) {
                $log .= "<strong>ATENCIÓN:</strong> El empleado $nombre $apellidos no se ha encontrado en la BD y no " .
                        "se ha podido enviar su nómina<br/>";
                continue;
            }

            $this->pdfCut->init("$path/$file");
            $pdfExtracted = $this->pdfCut->cut($numPage);
            $newFile = "$path/$nombre $apellidos.pdf";
            $result = $pdfExtracted->saveAs($newFile);

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

            $this->sendEmail($emailToSend, $newFile, $nombre);

            $log .= "Se ha enviado correctamente la nómina a $nombre $apellidos<br/>";
        }
        return $log;
    }

    /**
     * @param string|null $emailToSend
     * @param string $newFile
     * @param string $nombre
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendEmail(?string $emailToSend, string $newFile, string $nombre): void
    {
        if ($_SERVER['APP_ENV'] != 'prod') {
            return;
        }
        $email = (new Email())
            ->from($_SERVER['MAILER_SEND_BY'])
            ->to($emailToSend)
            ->subject('Su nómina del último mes')
            ->attachFromPath($newFile)
            ->text("Hola {$nombre}, le adjuntamos su última nómina")
            ->html("Hola $nombre,<br/><br/>Le adjuntamos su última nómina.<br/><br/>Saludos,");

        $transport = new GmailTransport($_SERVER['GMAIL_USERNAME'], $_SERVER['GMAIL_PASSWORD']);
        $mailer = new Mailer($transport);
        $mailer->send($email);
    }
}
