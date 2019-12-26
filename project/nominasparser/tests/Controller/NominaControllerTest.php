<?php

namespace Test\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NominaControllerTest extends WebTestCase
{
    /**
     * Se copia el fichero de nóminas, ya que el proceso lo termina moviendo
     */
    protected function setUp(): void
    {
        if (!file_exists('/var/www/html/nominasparser/tests/resources/IRASA_NOMINAS_TEST.pdf')) {
            copy(
                '/var/www/html/nominasparser/tests/resources/IRASA_NOMINAS_TEST_original.pdf',
                '/var/www/html/nominasparser/tests/resources/IRASA_NOMINAS_TEST.pdf'
            );
        }
    }

    protected function tearDown(): void
    {
        $nominasDir = self::$kernel->getProjectDir() . "/data/nominas/IRASA_NOMINAS_TEST";
        if (file_exists($nominasDir)) {
            $this->deleteDirectory($nominasDir);
        }
    }

    public function testIndexNew()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('span', 'Parseador de nóminas de Irasa');
        $this->assertEquals(
            1,
            $crawler->filter('html:contains("Introduce el fichero de nóminas")')->count()
        );
    }

    public function testIndexPost()
    {
        $client = static::createClient();

        $pdf = new UploadedFile(
            self::$kernel->getProjectDir() . '/tests/resources/IRASA_NOMINAS_TEST.pdf',
            'IRASA_NOMINAS_TEST.pdf',
            'application/pdf'
        );

        $params = [];
        $params['mode'] = 'test';
        $params['pdf'] = $pdf;

        $client->request('POST', '/', ['nomina_pdf' => $params], ['nomina_pdf' => $params]);

        $this->assertFileExists(
            self::$kernel->getProjectDir() . '/data/nominas/IRASA_NOMINAS_TEST/Antonio Lopez Salas.pdf'
        );
        $this->assertSelectorTextContains('div', 'Se ha enviado correctamente la nómina a Antonio Lopez Salas');
    }

    private function deleteDirectory(string $dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }
}
