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

    protected function setUp()
    {
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

        $this->assertEquals('Se ha enviado correctamente la nómina a John Doe<br/>', $result);
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

        $page->expects($this->exactly($numPages))
            ->method('getText')
            ->will($this->returnValue(' DOE, J	OHN 	
 50000  ZARAGOZ	A'));

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

        $this->assertEquals(
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

        $this->assertEquals('', $result);
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
        $this->assertEquals(
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
        $this->assertEquals(
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

        $this->pdfCut
            ->expects($this->exactly(2))
            ->method('init')
            ->with($this->equalTo('/tmp/file.pdf'));
        $this->pdfCut
            ->expects($this->exactly(2))
            ->method('cut')
            ->will($this->returnValue($this->pdfMock(true, 2)));

        $pdfParser = new PdfParser($this->parser, $this->empleadoRepository, $this->pdfCut);

        $result = $pdfParser->execute('/tmp', 'file.pdf', 'test', []);

        $nombre = $empleado->getNombre();
        $apellidos = $empleado->getApellidos();
        $this->assertEquals("Se ha enviado correctamente la nómina a $nombre $apellidos<br/>", $result);
    }

}
