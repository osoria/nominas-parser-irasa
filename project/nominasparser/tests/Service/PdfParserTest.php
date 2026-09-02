<?php

namespace Test\App\Service;

use App\Entity\Empleado;
use App\Repository\EmpleadoRepository;
use App\Service\PdfCutInterface;
use App\Service\PdfParser;
use mikehaertl\pdftk\Pdf;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class PdfParserTest extends TestCase
{
    /** @var PdfParser */
    private $pdfParser;

    /** @var Parser */
    private $parser;

    /** @var EmpleadoRepository */
    private $empleadoRepository;

    /** @var PdfCutInterface */
    private $pdfCut;

    public function configureFindOneByEmpleadoRepository($result, int $num = 1): void
    {
        $this->empleadoRepository
            ->expects($this->exactly($num))
            ->method('findOneBy')
            ->with($this->equalTo(['nombre' => 'JOHN', 'apellidos' => 'DOE']))
            ->will($this->returnValue($result));
    }

    public function configureSaveEmpleadoRepository(int $num = 1): void
    {
        $this->empleadoRepository
            ->expects($this->exactly($num))
            ->method('save')
            ->willReturn(null);
    }

    protected function setUp()
    {
        $_SERVER['EMAIL_TEST'] = 'test@test.com';
        $_SERVER['MAILER_SEND_BY'] = 'sender@test.com';
        $_SERVER['GMAIL_USERNAME'] = 'gmailuser';
        $_SERVER['GMAIL_PASSWORD'] = 'gmailpass';
        $_SERVER['APP_ENV'] = 'test';

        $this->parser = $this->createMock(Parser::class);
        $this->empleadoRepository = $this->createMock(EmpleadoRepository::class);
        $this->pdfCut = $this->createMock(PdfCutInterface::class);

        $this->pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);
    }

    public function testExecuteAllMode()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $this->configureFindOneByEmpleadoRepository($this->empleadoStub());
        $this->configureSaveEmpleadoRepository();

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock()));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $this->assertStringContainsString('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
    }

    public function testExecuteFindsEmployeeByDNI()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdfWithDni()));

        $this->empleadoRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['dni' => '25425665Q']))
            ->will($this->returnValue($this->empleadoStub()));

        $this->configureSaveEmpleadoRepository();

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock()));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $this->assertStringContainsString('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
    }

    public function testExecuteFallsBackToNameWhenDniNotFoundInDb()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdfWithDni()));

        $empleado = $this->empleadoStub();
        $this->empleadoRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function (array $criteria) use ($empleado) {
                return isset($criteria['dni']) ? null : $empleado;
            }));

        $this->configureSaveEmpleadoRepository();

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock()));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $this->assertStringContainsString('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
    }

    private function mockDocumentPdf(int $numPages = 1): Document
    {
        $documentPdf = $this->createMock(Document::class);
        $documentPdf
            ->expects($this->once())
            ->method('getPages')
            ->will($this->returnValue($this->pagesMock($numPages)));

        return $documentPdf;
    }

    private function empleadoStub(): Empleado
    {
        $empleado = new Empleado();

        return $empleado->setNombre('John')->setApellidos('Doe')->setEmail('john@doe.com')->setId(1);
    }

    private function pdfMock(?bool $returnSaveAs = true, int $numExecuted = 1): Pdf
    {
        $pdf = $this->createMock(Pdf::class);

        $pdf->expects($this->exactly($numExecuted))
            ->method('saveAs')
            ->with('/tmp/John Doe.pdf')
            ->will($this->returnValue($returnSaveAs));

        return $pdf;
    }

    private function pagesMock(int $numPages = 1): array
    {
        $page = $this->createMock(Page::class);

        $text = " DOE, JOHN\n";
        $text .= " 50000 ZARAGOZA\n";
        $text .= " NIF. B99408312\n";
        $text .= " PERIODO\n";
        $text .= " MENS 01 JUL 26 a 31 JUL 26\n";

        $page->expects($this->exactly($numPages))
            ->method('getText')
            ->will($this->returnValue($text));

        return array_fill(0, $numPages, $page);
    }

    private function mockDocumentPdfWithDni(int $numPages = 1): Document
    {
        $documentPdf = $this->createMock(Document::class);
        $documentPdf
            ->expects($this->once())
            ->method('getPages')
            ->will($this->returnValue($this->pagesMockWithDni($numPages)));

        return $documentPdf;
    }

    private function pagesMockWithDni(int $numPages = 1): array
    {
        $page = $this->createMock(Page::class);

        $text = " DOE, JOHN\n";
        $text .= " 50000 ZARAGOZA\n";
        $text .= " NIF. B99408312\n";
        $text .= " D.N.I. 25425665Q\n";
        $text .= " PERIODO\n";
        $text .= " MENS 01 JUL 26 a 31 JUL 26\n";

        $page->expects($this->exactly($numPages))
            ->method('getText')
            ->will($this->returnValue($text));

        return array_fill(0, $numPages, $page);
    }

    public function testExecuteModeSelecWithoutEmpleadosSelec()
    {
        $result = $this->pdfParser->execute('/tmp', 'file.pdf', 'selec', null);

        $this->assertEquals('<strong>ATENCIÓN, No se ha seleccionado a ningún empleado</strong>', $result);
    }

    public function testExecuteModeAllNotFoundEmpleado()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $this->configureFindOneByEmpleadoRepository(null);

        $result = $this->pdfParser->execute('/tmp', 'file.pdf', 'all', null);

        $this->assertStringContainsString(
            "<strong>ATENCIÓN:</strong> El empleado John Doe no se ha encontrado en la BD y no se ha podido enviar su nómina<br/>",
            $result
        );
    }

    public function testExecuteModeSelecNotInSelected()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $this->configureFindOneByEmpleadoRepository($this->empleadoStub());

        $result = $this->pdfParser->execute('/tmp', 'file.pdf', 'selec', [2, 3]);

        $this->assertStringNotContainsString('Se ha enviado correctamente', $result);
        $this->assertStringNotContainsString('no se ha encontrado', $result);
    }

    public function testExecuteNotSaved()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $this->configureFindOneByEmpleadoRepository($this->empleadoStub());

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock(false)));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $newFile = '/tmp/John Doe.pdf';
        $this->assertStringContainsString(
            "<strong>ATENCIÓN:</strong> No se ha podido guardar la nómina $newFile, por lo que no se ha podido enviar<br/>",
            $result
        );
    }

    public function testExecuteNotEmail()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $empleado = $this->empleadoStub()->setEmail(null);
        $this->configureFindOneByEmpleadoRepository($empleado);

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock(true)));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $nombre = $empleado->getNombre();
        $apellidos = $empleado->getApellidos();
        $this->assertStringContainsString(
            "<strong>ATENCIÓN:</strong> El empleado $nombre $apellidos no tiene un email asociado<br/>",
            $result
        );
    }

    public function testExecuteModeTest()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf(2)));

        $empleado = $this->empleadoStub();
        $this->configureFindOneByEmpleadoRepository($empleado, 2);
        $this->configureSaveEmpleadoRepository(1);

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock(true)));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'test', []);

        $nombre = $empleado->getNombre();
        $apellidos = $empleado->getApellidos();
        $this->assertStringContainsString("Se ha enviado correctamente la nómina a $nombre $apellidos<br/>", $result);
    }

    public function testExecuteModeCustom()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $this->configureFindOneByEmpleadoRepository($this->empleadoStub());
        $this->configureSaveEmpleadoRepository();

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock()));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'custom', [], 'custom@email.com');

        $this->assertStringContainsString('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
    }

    public function testExecuteSkipAlreadySentPeriod()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdf()));

        $empleado = $this->empleadoStub()->setPeriodo('MENS 01 JUL 26 a 31 JUL 26');
        $this->configureFindOneByEmpleadoRepository($empleado);

        $this->empleadoRepository
            ->expects($this->never())
            ->method('save');

        $this->pdfCut
            ->expects($this->never())
            ->method('init');
        $this->pdfCut
            ->expects($this->never())
            ->method('cut');

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $this->assertStringContainsString('No se ha enviado la nómina a John Doe porque se envió previamente', $result);
        $this->assertStringNotContainsString('Se ha enviado correctamente', $result);
    }

    public function testExecuteSendsWhenPeriodDifferent()
    {
        $this->parser
            ->expects($this->once())
            ->method('parseFile')
            ->with($this->equalTo('/tmp/file.pdf'))
            ->will($this->returnValue($this->mockDocumentPdfWithPeriod('MENS 16 JUL 26 a 31 JUL 26')));

        // El empleado ya recibió la nómina ordinaria del mes, pero la paga extra
        // llega con un periodo distinto (mismo mes, rango diferente) y debe enviarse.
        $empleado = $this->empleadoStub()->setPeriodo('MENS 01 JUL 26 a 31 JUL 26');
        $this->configureFindOneByEmpleadoRepository($empleado);
        $this->configureSaveEmpleadoRepository();

        $this->pdfCut
            ->expects($this->once())
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->once())
            ->method('cut')
            ->with($this->equalTo(1))
            ->will($this->returnValue($this->pdfMock()));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'all', []);

        $this->assertStringContainsString('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
        $this->assertStringNotContainsString('porque se envió previamente', $result);
    }

    private function mockDocumentPdfWithPeriod(string $periodo, int $numPages = 1): Document
    {
        $documentPdf = $this->createMock(Document::class);
        $documentPdf
            ->expects($this->once())
            ->method('getPages')
            ->will($this->returnValue($this->pagesMockWithPeriod($periodo, $numPages)));

        return $documentPdf;
    }

    private function pagesMockWithPeriod(string $periodo, int $numPages = 1): array
    {
        $page = $this->createMock(Page::class);

        $text = " DOE, JOHN\n";
        $text .= " 50000 ZARAGOZA\n";
        $text .= " NIF. B99408312\n";
        $text .= " PERIODO\n";
        $text .= " $periodo\n";

        $page->expects($this->exactly($numPages))
            ->method('getText')
            ->will($this->returnValue($text));

        return array_fill(0, $numPages, $page);
    }
}