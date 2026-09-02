<?php

namespace App\Service;

use App\Repository\EmpleadoRepository;
use Smalot\PdfParser\Parser;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class PdfParser implements PdfParserInterface
{
    private $parser;
    private $empleadoRepository;
    private $pdfCut;

    public function __construct(Parser $parser, EmpleadoRepository $empleadoRepository, PdfCutInterface $pdfCut)
    {
        $this->parser = $parser;
        $this->empleadoRepository = $empleadoRepository;
        $this->pdfCut = $pdfCut;
    }

    public function execute(string $path, string $file, string $mode, ?array $empleadosSelected, string $customEmail = ''): string
    {
        $log = '';
        $messagesPost = [];
        if ($mode == 'selec' && !$empleadosSelected) {
            $log .= "<strong>ATENCIÓN, No se ha seleccionado a ningún empleado</strong>";
            return $log;
        }

        try {
            $pdf = $this->parser->parseFile("$path/$file");
            $pages = $pdf->getPages();
        } catch (\Throwable $e) {
            return $log . "<strong>ERROR:</strong> No se pudo parsear el PDF: " . $e->getMessage() . "<br/>";
        }

        $numPage = 0;
        $numSended = 0;
        $log .= "Procesando " . count($pages) . " páginas...<br/>";

        foreach ($pages as $page) {
            $numPage++;
            try {
                $this->processPage($page, $numPage, $path, $file, $mode, $empleadosSelected, $customEmail, $numSended, $log, $messagesPost);
            } catch (\Throwable $e) {
                $log .= "<strong>ERROR FATAL en página $numPage:</strong> " . get_class($e) . ": " . $e->getMessage() . "<br/>";
            }
        }

        if (!empty($messagesPost)) $log .= "<br/>" . implode('<br/>', $messagesPost);
        return $log;
    }

    private function processPage($page, int $numPage, string $path, string $file, string $mode, ?array $empleadosSelected, string $customEmail, int &$numSended, string &$log, array &$messagesPost): void
    {
        $text = $page->getText();
        if (!$text) {
            $log .= "<strong>DEBUG:</strong> Página $numPage sin texto<br/>";
            return;
        }

        $text = str_replace("\r", "\n", $text);

        $dni = $this->extractDNI($text);

        list($apellidos, $nombre) = $this->extractName($text);
        if (!$apellidos || !$nombre) {
            $log .= "<strong>DEBUG:</strong> No se pudo extraer nombre de la página $numPage<br/>";
            return;
        }

        $periodo = $this->extractPeriodo($text);

        $nombreDisplay = ucwords(strtolower($nombre));
        $apellidosDisplay = ucwords(strtolower($apellidos));

        $empleado = null;
        if ($dni) {
            $empleado = $this->empleadoRepository->findOneBy(['dni' => $dni]);
        }
        if (!$empleado) {
            $empleado = $this->empleadoRepository->findOneBy(['nombre' => $nombre, 'apellidos' => $apellidos]);
            if (!$empleado && strlen($nombre) >= 3) {
                try {
                    $empleado = $this->empleadoRepository->findOneByApellidosAndNombreContains($apellidos, $nombre);
                } catch (\Throwable $e) {}
            }
        }

        if (!$empleado) {
            $log .= "<strong>ATENCIÓN:</strong> El empleado $nombreDisplay $apellidosDisplay no se ha encontrado en la BD y no " .
                    "se ha podido enviar su nómina<br/>";
            return;
        }
        if ($mode == 'selec' && !in_array($empleado->getId(), $empleadosSelected)) return;
        if ($periodo !== null && $empleado->getPeriodo() === $periodo) {
            $messagesPost[] = "No se ha enviado la nómina a $nombreDisplay $apellidosDisplay porque se envió previamente";
            return;
        }

        // Intentar recortar el PDF para esta página
        try {
            $this->pdfCut->init("$path/$file");
            $pdfExtracted = $this->pdfCut->cut($numPage);
        } catch (\Throwable $e) {
            $log .= "<strong>ERROR:</strong> No se pudo cortar página $numPage: " . $e->getMessage() . "<br/>";
            return;
        }

        $newFile = "$path/$nombreDisplay $apellidosDisplay.pdf";
        try {
            if (!$pdfExtracted->saveAs($newFile)) {
                $log .= "<strong>ATENCIÓN:</strong> No se ha podido guardar la nómina $newFile, por lo que no se ha podido enviar<br/>";
                return;
            }
        } catch (\Throwable $e) {
            $log .= "<strong>ERROR:</strong> No se pudo guardar $newFile: " . $e->getMessage() . "<br/>";
            return;
        }

        if (!$empleado->getEmail()) {
            $log .= "<strong>ATENCIÓN:</strong> El empleado $nombreDisplay $apellidosDisplay no tiene un email asociado<br/>";
            return;
        }

        $emailToSend = $this->calculateEmailToSend($empleado->getEmail(), $mode, $customEmail);
        if ($mode == 'test' && $numSended) return;

        $this->sendEmail($emailToSend, $newFile, $nombreDisplay);
        $numSended++;
        if ($periodo !== null) {
            $empleado->setPeriodo($periodo);
            $this->empleadoRepository->save($empleado);
        }
        $log .= "Se ha enviado correctamente la nómina a $nombreDisplay $apellidosDisplay<br/>";
    }

    private function extractName(string $text): array
    {
        $t = preg_replace('/\t+/', ' ', $text);
        $lines = array_values(array_filter(explode("\n", $t), function ($l) {
            return trim($l) !== '';
        }));
        $numLines = count($lines);

        if ($numLines >= 5) {
            return $this->extractFromLine($lines[0]);
        }

        return $this->extractFromLine($t);
    }

    private function extractFromLine(string $raw): array
    {
        $raw = trim($raw);
        $parts = explode(',', $raw, 2);
        if (!isset($parts[1])) return ['', ''];
        $ap = trim(preg_replace('/\s+/', ' ', $parts[0]));
        $nom = trim(preg_replace('/\s+/', ' ', $parts[1]));

        if (strlen($ap) < 3) return ['', ''];

        $markers = '\d{4,5}\b|CL\s|PZ\s|CN\s|AV\s|CR\s|NIF\b|ZARAGOZA|TAUSTE|EJEA|INVERSIONES';
        if (preg_match('/^(.+?)\s+(' . $markers . ')/ui', $nom, $m)) {
            $nom = trim($m[1]);
        }

        if (strlen($nom) < 2) return ['', ''];

        return [$ap, $nom];
    }

    /**
     * Extrae el DNI/NIF/NIE del trabajador del texto de la página.
     *
     * Formatos soportados:
     *  - NIF: 8 dígitos + letra (ej: 25425665Q)
     *  - NIE: X/Y/Z + 7 dígitos + letra (ej: Y2775123B)
     *
     * Se ignora el CIF de la empresa (ej: NIF. B99408312), que empieza por letra
     * y no coincide con ninguno de los patrones anteriores.
     */
    private function extractDNI(string $text): ?string
    {
        if (preg_match('/\b((?:[0-9]{8}|[XYZ][0-9]{7})[A-Z])\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Extrae el periodo completo de la nómina del texto de la página.
     *
     * Formato esperado: "MENS 01 AGO 26 a 31 AGO 26".
     * Devuelve el texto normalizado (espacios colapsados y sin espacios en los
     * extremos) para poder compararlo de forma fiable con el periodo guardado
     * en un envío anterior. Así, la paga extra (con un periodo distinto aunque
     * coincida el mes) no se bloquea por haber enviado ya la nómina ordinaria.
     */
    private function extractPeriodo(string $text): ?string
    {
        if (preg_match('/MENS\s+\d{2}\s+[A-Z]{3}\s+\d{2}\s+a\s+\d{2}\s+[A-Z]{3}\s+\d{2}/i', $text, $m)) {
            return preg_replace('/\s+/', ' ', trim($m[0]));
        }
        return null;
    }

    private function sendEmail(?string $emailToSend, string $newFile, string $nombre): void
    {
        if ($_SERVER['APP_ENV'] != 'prod') return;
        $email = (new Email())->from($_SERVER['MAILER_SEND_BY'])->to($emailToSend)
            ->subject('Su nómina del último mes')->attachFromPath($newFile)
            ->text("Hola {$nombre}, le adjuntamos su última nómina")
            ->html("Hola $nombre,<br/><br/>Le adjuntamos su última nómina.<br/><br/>Saludos,");
        (new Mailer(new GmailTransport($_SERVER['GMAIL_USERNAME'], $_SERVER['GMAIL_PASSWORD'])))->send($email);
    }

    private function calculateEmailToSend(string $email, string $mode, string $customEmail = ''): string
    {
        if ($mode == 'test') return $_SERVER['EMAIL_TEST'];
        if ($mode == 'custom' && $customEmail) return $customEmail;
        return $email;
    }
}